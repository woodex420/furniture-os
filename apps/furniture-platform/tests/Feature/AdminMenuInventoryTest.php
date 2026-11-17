<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Inventory covers owner navigation, not every controller, action or permission. */
class AdminMenuInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_menu_inventory_matches_rendered_navigation_with_flags_on_and_off(): void
    {
        $entries = json_decode(file_get_contents(base_path('admin-menu-inventory.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($entries);
        $names = array_map(fn (array $entry): string => $entry['group'].'/'.$entry['name'], $entries);
        $this->assertCount(count(array_unique($names)), $names, 'Inventory has duplicate menu entries');
        $flags = ['home_editor_enabled', 'service_copy_enabled', 'city_pages_enabled', 'blog_posts_enabled', 'media_library_enabled'];
        $owner = User::factory()->create(['is_owner' => true]);

        foreach ([false, true] as $enabled) {
            foreach ($flags as $flag) {
                config(['furniture.'.$flag => $enabled]);
            }
            $html = $this->withoutVite()->actingAs($owner)->get('/admin')->assertOk()->getContent();
            $document = new DOMDocument;
            $prior = libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
            libxml_clear_errors();
            libxml_use_internal_errors($prior);
            $xpath = new DOMXPath($document);
            $actual = [];
            foreach ($xpath->query('//nav[@aria-label="Modules"]/details') as $group) {
                $groupName = trim($xpath->query('./summary/span[1]', $group)->item(0)->textContent);
                foreach ($xpath->query('./a[contains(concat(" ", normalize-space(@class), " "), " nav-item ")] | ./span[contains(concat(" ", normalize-space(@class), " "), " nav-item ")]', $group) as $item) {
                    $name = trim($xpath->query('./span[1]', $item)->item(0)->textContent);
                    $actual[$groupName.'/'.$name] = ['tag' => $item->nodeName, 'href' => $item->getAttribute('href')];
                }
            }
            $this->assertSame($names, array_keys($actual), 'Rendered owner menu differs from inventory');
            foreach ($entries as $entry) {
                $this->assertContains($entry['status'], ['partial', 'pending']);
                $this->assertIsString($entry['scope']);
                $this->assertNotEmpty($entry['scope']);
                $available = $entry['status'] === 'partial' && ($entry['flag'] === null || $enabled);
                if ($entry['flag'] !== null) {
                    $this->assertContains($entry['flag'], $flags);
                }
                $item = $actual[$entry['group'].'/'.$entry['name']];
                $this->assertSame($available ? 'a' : 'span', $item['tag'], $entry['name']);
                $this->assertSame($available ? $entry['path'] : '', $item['href'], $entry['name']);
            }
        }
    }
}
