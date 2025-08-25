<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;

final class AuthServiceTest extends TestCase
{
    public function testCreateAndVerifyToken(): void
    {
        // set minimal env for test
        $_ENV['JWT_SECRET'] = 'test-secret-for-unit';
        $_ENV['JWT_ALGORITHM'] = 'HS256';
        $_ENV['JWT_EXPIRY'] = '3600';

        $auth = new AuthService();
        $user = ['id' => 123, 'role' => 'student'];
        $token = $auth->createToken($user);
        $this->assertIsString($token);

        $payload = $auth->verifyToken($token);
        $this->assertEquals(123, $payload->sub);
        $this->assertEquals('student', $payload->role);
    }
}
