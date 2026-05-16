<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstallerSecurityTest extends TestCase
{
    protected string $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = file_get_contents(base_path('public/install.php'));
    }

    public function test_installer_uses_csrf_token(): void
    {
        $this->assertStringContainsString("\$_SESSION['csrf_token']", $this->source);
        $this->assertStringContainsString('bin2hex(random_bytes(32))', $this->source);
        $this->assertStringContainsString('hash_equals(', $this->source);
        $this->assertStringContainsString('name="csrf_token"', $this->source);
    }

    public function test_installer_validates_password_confirmation(): void
    {
        $this->assertStringContainsString('admin_password_confirmation', $this->source);
        $this->assertStringContainsString('Konfirmasi password tidak cocok', $this->source);
    }

    public function test_locked_message_does_not_leak_lockfile_path(): void
    {
        $this->assertStringContainsString('Installer terkunci. Aplikasi sudah terinstall.', $this->source);
        $this->assertStringNotContainsString('Hapus storage/installed.lock', $this->source);
    }

    public function test_passwords_are_scrubbed_from_install_log(): void
    {
        $this->assertStringContainsString('[redacted]', $this->source);
    }

    public function test_db_test_does_not_leak_credentials(): void
    {
        $this->assertStringContainsString('Koneksi database gagal. Periksa host/port/credential.', $this->source);
    }
}
