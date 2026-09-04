<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Functional test of the admin file manager backend (step 02): upload,
 * list and delete through the real HTTP layer, including the ROLE_SUPER_ADMIN
 * gate inherited from the ^/api/admin firewall access_control rule.
 */
class FileAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM file');

        parent::tearDown();
    }

    public function testFullLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request('POST', '/api/admin/files', [], ['file' => $this->makeImageUpload()]);
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('img', $created['type']);
        self::assertCount(4, $created['thumbnails']);
        self::assertNotSame('/build/images/placeholder.svg', $created['url']);
        $id = $created['id'];

        $client->request('GET', '/api/admin/files');
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode($client->getResponse()->getContent(), true));

        $client->request('DELETE', "/api/admin/files/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/admin/files');
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }

    public function testUploadingAnUnsupportedTypeReturnsUnprocessableEntity(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tmpPath, 'plain text content');
        $uploadedFile = new UploadedFile($tmpPath, 'notes.txt', 'text/plain', null, true);

        $client->request('POST', '/api/admin/files', [], ['file' => $uploadedFile]);
        self::assertResponseStatusCodeSame(422);

        unlink($tmpPath);
    }

    public function testAnonymousCannotAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/files');
        self::assertResponseStatusCodeSame(401);
    }

    private function makeImageUpload(): UploadedFile
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_test_').'.png';

        $image = imagecreatetruecolor(300, 200);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 20, 30));
        imagepng($image, $tmpPath);
        imagedestroy($image);

        return new UploadedFile($tmpPath, 'sample.png', 'image/png', null, true);
    }
}
