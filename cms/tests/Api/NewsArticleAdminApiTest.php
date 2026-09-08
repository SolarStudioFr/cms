<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NewsArticleAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM news_article');
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM news_category');

        parent::tearDown();
    }

    public function testFullCrudLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/news', [
            'title' => 'Test actualite',
            'content' => 'Test content',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('draft', $created['status']);
        self::assertSame('test-actualite', $created['slug']);
        $id = $created['id'];

        $client->request('GET', '/api/admin/news');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);

        $client->request(
            'PATCH',
            "/api/admin/news/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'published']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('published', json_decode($client->getResponse()->getContent(), true)['status']);

        $client->request(
            'PATCH',
            "/api/admin/news/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'archived']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('archived', json_decode($client->getResponse()->getContent(), true)['status']);

        $client->request('DELETE', "/api/admin/news/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', "/api/admin/news/{$id}");
        self::assertResponseStatusCodeSame(404);
    }

    public function testCoverImageRoundTrips(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/news', [
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

        $client->jsonRequest('POST', '/api/admin/news', ['title' => 'Fallback', 'content' => 'Plain text']);
        self::assertResponseIsSuccessful();
        self::assertNull(json_decode($client->getResponse()->getContent(), true)['builderData']);
    }

    public function testSeoAndSocialFieldsRoundTrip(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/news', [
            'title' => 'SEO actualite',
            'content' => 'Content',
            'seoTitle' => 'SEO title',
            'seoDescription' => 'SEO description',
            'ogImageUrl' => '/upload/img/webp/og.webp',
            'ogType' => 'profile',
            'canonicalUrl' => 'https://example.com/seo-actualite',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('SEO title', $created['seoTitle']);
        self::assertSame('SEO description', $created['seoDescription']);
        self::assertSame('/upload/img/webp/og.webp', $created['ogImageUrl']);
        self::assertSame('profile', $created['ogType']);
        self::assertSame('https://example.com/seo-actualite', $created['canonicalUrl']);
    }

    public function testCategoryCanBeAssignedChangedAndCleared(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/news/categories', ['name' => 'Vie du studio']);
        self::assertResponseIsSuccessful();
        $category = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('vie-du-studio', $category['slug']);

        $client->jsonRequest('POST', '/api/admin/news', [
            'title' => 'Categorized article',
            'content' => 'Content',
            'categoryId' => $category['id'],
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Vie du studio', $created['category']['name']);

        $client->request(
            'PATCH',
            "/api/admin/news/{$created['id']}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['categoryId' => null]),
        );
        self::assertResponseIsSuccessful();
        self::assertNull(json_decode($client->getResponse()->getContent(), true)['category']);
    }

    public function testPartialPatchWithoutCategoryIdLeavesCategoryUntouched(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/news/categories', ['name' => 'Kept']);
        self::assertResponseIsSuccessful();
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->jsonRequest('POST', '/api/admin/news', [
            'title' => 'Quick archive',
            'content' => 'Content',
            'categoryId' => $category['id'],
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);

        // Same shape as the list view's quick "archive" action, which only sends `status`.
        $client->request(
            'PATCH',
            "/api/admin/news/{$created['id']}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'archived']),
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('archived', $updated['status']);
        self::assertSame('Kept', $updated['category']['name']);
    }

    public function testAnonymousCannotWrite(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/admin/news', [
            'title' => 'Should not be created',
            'content' => 'Should not be created',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAnonymousCannotListAdminNewsArticles(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/news');

        self::assertResponseStatusCodeSame(401);
    }
}
