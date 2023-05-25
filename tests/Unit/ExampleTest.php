<?php

namespace Tests\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_homepage()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('<h1 style=\'color:red\'>Aliens</h1>');
    }

    public function test_date()
    {
        $response = $this->get('/date');
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->isoFormat('LL'));
        $response->assertSee(Carbon::now()->isoFormat('LLLL'));
        $response->assertSee(Carbon::now()->toDateString());
    }

    public function test_export_csv()
    {
        $user = factory(User::class)->create();
        $response = $this->actingAs($user)->get('/export/csv');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
        $response->assertHeader('Content-Disposition', 'attachment; filename="aliensExport.csv"');
    }

    public function test_upload()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg');
        $response = $this->actingAs($user)->post('/upload', ['file' => $file]);
        $response->assertStatus(200);
        Storage::disk('public')->assertExists($file->hashName());
    }

    public function test_csv_upload()
    {
        $user = factory(User::class)->create();
        Storage::fake('public');
        $file = UploadedFile::fake()->createWithContent('aliens.csv', 'name,email,location,date,time,scary
            John Doe,john@example.com,New York,2022-01-01,12:00:00,1
            Jane Doe,jane@example.com,Los Angeles,2022-01-02,13:00:00,0
            ');
        $response = $this->actingAs($user)->post('/csvupload', ['file' => $file]);
        $response->assertStatus(302);
        $response->assertRedirect('/');
        $response->assertSessionHas('message', 'Import successful.');
        Storage::disk('public')->assertExists($file->hashName());
    }
}
