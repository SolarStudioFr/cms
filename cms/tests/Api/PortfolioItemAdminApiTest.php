<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PortfolioItemAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        // portfolio_item_tag rows cascade-delete with portfolio_item, so
        // portfolio_tag alone needs a separate cleanup pass.
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM portfolio_item');
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM portfolio_tag');

        parent::tearDown();
    }

    public function testFullCrudLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/portfolio', [
            'title' => 'Test realisation',
            'content' => 'Test content',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('draft', $created['status']);
        self::assertSame('test-realisation', $created['slug']);
        $id = $created['id'];

        $client->request('GET', '/api/admin/portfolio');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);

        $client->request(
            'PATCH',
            "/api/admin/portfolio/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'published']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('published', json_decode($client->getResponse()->getContent(), true)['status']);

        $client->request(
            'PATCH',
            "/api/admin/portfolio/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'archived']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('archived', json_decode($client->getResponse()->getContent(), true)['status']);

        $client->request('DELETE', "/api/admin/portfolio/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', "/api/admin/portfolio/{$id}");
        self::assertResponseStatusCodeSame(404);
    }

    public function testCoverImageRoundTrips(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/portfolio', [
            'title' => 'With cover',
            'content' => 'Content',
            'coverImageUrl' => '/upload/img/webp/example.webp',
            'coverImageAlt' => 'Example',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('/upload/img/webp/example.webp', $created['coverImageUrl']);
        self::assertSame('Example', $created['coverImageAlt']);
    }

    public function testBuilderDataDefaultsToNullForTheFallbackEditor(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/portfolio', ['title' => 'Fallback', 'content' => 'Plain text']);
        self::assertResponseIsSuccessful();
        self::assertNull(json_decode($client->getResponse()->getContent(), true)['builderData']);
    }

    public function testSeoAndSocialFieldsRoundTrip(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/portfolio', [
            'title' => 'SEO realisation',
            'content' => 'Content',
            'seoTitle' => 'SEO title',
            'seoDescription' => 'SEO description',
            'ogImageUrl' => '/upload/img/webp/og.webp',
            'ogType' => 'product',
            'canonicalUrl' => 'https://example.com/seo-realisation',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('SEO title', $created['seoTitle']);
        self::assertSame('SEO description', $created['seoDescription']);
        self::assertSame('/upload/img/webp/og.webp', $created['ogImageUrl']);
        self::assertSame('product', $created['ogType']);
        self::assertSame('https://example.com/seo-realisation', $created['canonicalUrl']);
    }

    public function testTagsCanBeAssignedAndReplaced(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/portfolio/tags', ['name' => 'Web']);
        self::assertResponseIsSuccessful();
        $webTag = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('web', $webTag['slug']);

        $client->jsonRequest('POST', '/api/admin/portfolio/tags', ['name' => 'Design']);
        self::assertResponseIsSuccessful();
        $designTag = json_decode($client->getResponse()->getContent(), true);

        $client->jsonRequest('POST', '/api/admin/portfolio', [
            'title' => 'Tagged realisation',
            'content' => 'Content',
            'tagIds' => [$webTag['id'], $designTag['id']],
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $created['tags']);
        self::assertSame(['Web', 'Design'], array_column($created['tags'], 'name'));

        $client->request(
            'PATCH',
            "/api/admin/portfolio/{$created['id']}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['tagIds' => [$webTag['id']]]),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $updated['tags']);
        self::assertSame('Web', $updated['tags'][0]['name']);

        $client->request(
            'PATCH',
            "/api/admin/portfolio/{$created['id']}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['tagIds' => []]),
        );
        self::assertResponseIsSuccessful();
        self::assertSame([], json_decode($client->getResponse()->getContent(), true)['tags']);
    }

    public function testPartialPatchWithoutTagIdsLeavesTagsUntouched(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/portfolio/tags', ['name' => 'Kept']);
        self::assertResponseIsSuccessful();
        $tag = json_decode($client->getResponse()->getContent(), true);

        $client->jsonRequest('POST', '/api/admin/portfolio', [
            'title' => 'Quick archive',
            'content' => 'Content',
            'tagIds' => [$tag['id']],
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);

        // Same shape as the list view's quick "archive" action, which only sends `status`.
        $client->request(
            'PATCH',
            "/api/admin/portfolio/{$created['id']}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'archived']),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('archived', $updated['status']);
        self::assertCount(1, $updated['tags']);
        self::assertSame('Kept', $updated['tags'][0]['name']);
    }

    public function testAnonymousCannotWrite(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/admin/portfolio', [
            'title' => 'Should not be created',
            'content' => 'Should not be created',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAnonymousCannotListAdminPortfolioItems(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/portfolio');

        self::assertResponseStatusCodeSame(401);
    }
}
