<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Shield\Entities\User;
use Config\ContentTypes;

/**
 * Choices offered by select fields in the editing forms. A submitted value is
 * accepted only if it is one of these choices.
 */
class ContentOptions
{
    public function __construct(
        private readonly BaseConnection $db,
        private readonly ContentTypes $types,
        private readonly ContentPolicy $policy,
    ) {
    }

    /**
     * @param array<string, mixed>|null $record Record being edited
     *
     * @return array<string, string> value => label
     */
    public function for(string $source, User $user, string $locale, ?array $record = null): array
    {
        return match ($source) {
            'articleCategories' => $this->articleCategories($user),
            'pageSections'      => $this->types->pageSections,
            'missionCategories' => array_map(static fn (array $c): string => $c['label'], $this->types->missionCategories),
            'department'        => $this->departments($user),
            'missions'          => $this->pairs('missions', 'name', $locale),
            'countries'         => $this->pairs('countries', 'name', null),
            'articles'          => $this->pairs('articles', 'title', $locale),
            'galleries'         => $this->pairs('galleries', 'title', $locale),
            'pages'             => $this->pages($locale, $record),
            'orgUnits'          => $this->orgUnits($locale, $record),
            'submissionTypes'   => array_map(static fn (array $t): string => $t['label'], config('Submissions')->types),
            default             => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function articleCategories(User $user): array
    {
        $categories = $this->types->articleCategories;

        if (! $user->can('missions.publish-activities')) {
            unset($categories['mission_activity']);
        }

        if ($this->policy->allowedDepartmentIds($user) === [] && ! $user->can('content.publish-any')) {
            unset($categories['news'], $categories['statement']);
        }

        if (! $this->policy->canPublishDiaspora($user)) {
            foreach ($this->types->diasporaArticleCategories as $category) {
                unset($categories[$category]);
            }
        }

        return $categories;
    }

    /**
     * @return array<string, string>
     */
    private function departments(User $user): array
    {
        $allowed = $this->policy->allowedDepartmentIds($user);
        $builder = $this->db->table('departments')->orderBy('name');

        if ($allowed !== null) {
            if ($allowed === []) {
                return [];
            }
            $builder->whereIn('id', $allowed);
        }

        return array_column($builder->get()->getResultArray(), 'name', 'id');
    }

    /**
     * @return array<string, string>
     */
    private function pairs(string $table, string $label, ?string $locale): array
    {
        $builder = $this->db->table($table)->select("id, {$label}")->orderBy($label);

        if ($locale !== null) {
            $builder->where('locale', $locale);
        }

        return array_column($builder->get()->getResultArray(), $label, 'id');
    }

    /**
     * @param array<string, mixed>|null $record
     *
     * @return array<string, string>
     */
    private function pages(string $locale, ?array $record): array
    {
        $builder = $this->db->table('pages')->where('locale', $locale)->orderBy('section')->orderBy('path');

        if ($record !== null && isset($record['path'])) {
            // A page cannot be its own parent or sit under its own children.
            $builder->where('id !=', $record['id'])->notLike('path', $record['path'] . '/', 'after');
        }

        $options = [];
        foreach ($builder->get()->getResultArray() as $page) {
            $options[$page['id']] = ($this->types->pageSections[$page['section']] ?? $page['section']) . ' › ' . str_replace('/', ' › ', $page['path']) . ' (' . $page['title'] . ')';
        }

        return $options;
    }

    /**
     * @param array<string, mixed>|null $record
     *
     * @return array<string, string>
     */
    private function orgUnits(string $locale, ?array $record): array
    {
        $builder = $this->db->table('org_units')->where('locale', $locale)->orderBy('name');

        if ($record !== null && isset($record['id'])) {
            $builder->where('id !=', $record['id']);
        }

        return array_column($builder->get()->getResultArray(), 'name', 'id');
    }
}
