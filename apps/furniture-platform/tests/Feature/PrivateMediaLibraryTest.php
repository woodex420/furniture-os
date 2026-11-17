<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PrivateMediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function png(int $width = 2, int $height = 2): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    private function chunk(string $type, string $body): string
    {
        return pack('N', strlen($body)).$type.$body.pack('N', crc32($type.$body));
    }

    private function file(string $bytes, string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    public function test_flag_auth_and_owner_permissions(): void
    {
        Storage::fake('furniture_media');
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner)->get('/admin/website/media')->assertNotFound();
        $this->post('/admin/website/media', ['title' => 'Test', 'image' => $this->file($this->png())])->assertNotFound();
        config(['furniture.media_library_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => false]))->get('/admin/website/media')->assertForbidden();
        $this->post('/admin/website/media', ['title' => 'Test', 'image' => $this->file($this->png())])->assertForbidden();
        $this->actingAs($owner)->withoutVite()->get('/admin/website/media')->assertOk()->assertSee('private')->assertSee('Add private image');
        $this->assertDatabaseCount('furniture_media', 0);
    }

    public function test_upload_is_reencoded_stored_privately_and_viewed_only_by_owner(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.media_library_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $png = $this->png();
        $png = substr($png, 0, 33).$this->chunk('tEXt', "Comment\0private metadata").substr($png, 33);
        $this->actingAs($owner)->post('/admin/website/media', ['title' => '<Furniture>', 'image' => $this->file($png, '../../evil.php')])->assertSessionHasNoErrors()->assertRedirect('/admin/website/media');
        $row = DB::table('furniture_media')->first();
        $this->assertNotNull($row);
        $this->assertSame('image/png', $row->mime);
        $this->assertSame(2, $row->width);
        $this->assertSame($owner->id, $row->created_by);
        $path = $row->id.'.png';
        Storage::disk('furniture_media')->assertExists($path);
        $this->assertStringNotContainsString('private metadata', Storage::disk('furniture_media')->get($path));
        $this->withoutVite()->get('/admin/website/media')->assertSee('&lt;Furniture&gt;', false)->assertDontSee('<Furniture>', false);
        $url = '/admin/website/media/'.$row->id.'/file';
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs(User::factory()->create(['is_owner' => false]))->get($url)->assertForbidden();
        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));
        $this->get('/storage/'.$path)->assertForbidden();
    }

    public function test_invalid_and_animated_files_and_large_dimensions_are_rejected(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.media_library_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $png = $this->png();
        $animated = substr($png, 0, 33).$this->chunk('acTL', pack('NN', 2, 0)).substr($png, 33);
        foreach (['<?php echo 1;', '<svg xmlns="http://www.w3.org/2000/svg"/>', substr($png, 0, 25), $animated, $this->png(2100, 2000)] as $bad) {
            $this->post('/admin/website/media', ['title' => 'Bad', 'image' => $this->file($bad)])->assertSessionHasErrors('image');
        }
        $this->post('/admin/website/media', ['title' => '', 'image' => $this->file($png)])->assertSessionHasErrors('title');
        $this->post('/admin/website/media', ['title' => 'Big', 'image' => UploadedFile::fake()->create('big.png', 2049, 'image/png')])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('furniture_media', 0);
        $this->assertSame([], Storage::disk('furniture_media')->allFiles());
    }

    public function test_revoked_owner_is_rechecked_and_the_file_is_removed(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.media_library_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        $this->post('/admin/website/media', ['title' => 'Test', 'image' => $this->file($this->png())])->assertForbidden();
        $this->assertDatabaseCount('furniture_media', 0);
        $this->assertSame([], Storage::disk('furniture_media')->allFiles());
    }

    public function test_failure_of_database_write_removes_the_processed_file(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.media_library_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]))->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "furniture_media"')) {
                throw new RuntimeException('Synthetic media failure');
            }
        });
        try {
            $this->post('/admin/website/media', ['title' => 'Test', 'image' => $this->file($this->png())]);
            $this->fail('Expected database failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic media failure', $exception->getMessage());
            $this->assertDatabaseCount('furniture_media', 0);
            $this->assertSame([], Storage::disk('furniture_media')->allFiles());
        }
    }

    public function test_jpeg_requires_exif_or_accepts_upright_and_rejects_rotated(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.media_library_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();
        imagedestroy($image);
        $response = $this->post('/admin/website/media', ['title' => 'JPEG', 'image' => $this->file($jpeg, 'image.jpg')]);
        if (! function_exists('exif_read_data')) {
            $response->assertSessionHasErrors('image');
            $this->assertDatabaseCount('furniture_media', 0);

            return;
        }
        $response->assertSessionHasNoErrors();
        $tiff = "II\x2a\x00\x08\x00\x00\x00\x01\x00\x12\x01\x03\x00\x01\x00\x00\x00\x06\x00\x00\x00\x00\x00\x00\x00";
        $app = "Exif\x00\x00".$tiff;
        $rotated = substr($jpeg, 0, 2)."\xff\xe1".pack('n', strlen($app) + 2).$app.substr($jpeg, 2);
        $this->post('/admin/website/media', ['title' => 'Rotated', 'image' => $this->file($rotated, 'image.jpg')])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('furniture_media', 1);
    }
}
