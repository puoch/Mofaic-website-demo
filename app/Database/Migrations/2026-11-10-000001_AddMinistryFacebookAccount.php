<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\I18n\Time;

/**
 * Adds the Ministry's official Facebook page, supplied by the project owner
 * on 2026-11-10, to Social media accounts so the footer icon links to it.
 * Skipped when a Facebook account is already entered; editors can change
 * or switch it off in the administration area.
 */
class AddMinistryFacebookAccount extends Migration
{
    private const URL = 'https://www.facebook.com/p/Ministry-of-Foreign-Affairs-and-International-Cooperation-of-South-Sudan-100075949121921/';

    public function up(): void
    {
        $platforms = array_column($this->db->table('social_accounts')->select('platform')->get()->getResultArray(), 'platform');

        foreach ($platforms as $platform) {
            if (strtolower(trim((string) $platform)) === 'facebook') {
                return;
            }
        }

        $now = Time::now('UTC')->toDateTimeString();
        $this->db->table('social_accounts')->insert([
            'platform'   => 'Facebook',
            'label'      => 'Ministry of Foreign Affairs and International Cooperation on Facebook',
            'url'        => self::URL,
            'sort_order' => 1,
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $this->db->table('social_accounts')->where('url', self::URL)->delete();
    }
}
