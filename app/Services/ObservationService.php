<?php

namespace App\Services;

use App\Models\Observation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ObservationService
{
    /**
     * @param  array<int, string>  $tags
     */
    public function syncSingleObservation(
        Model $model,
        string $context,
        ?string $body,
        array $tags,
        ?int $userId = null,
    ): void {
        $normalized = $this->normalizeBody($body);

        if ($normalized === null) {
            if (method_exists($model, 'detachObservationsByContext')) {
                $model->detachObservationsByContext($context);
            }

            return;
        }

        $latest = method_exists($model, 'latestObservation')
            ? $model->latestObservation($context, $tags)
            : null;

        if ($latest && $this->normalizeBody($latest->body) === $normalized) {
            return;
        }

        $observation = Observation::query()->create([
            'body' => $normalized,
            'audience_tags' => $this->uniqueTags($tags),
            'created_by' => $userId,
        ]);

        if (method_exists($model, 'attachObservation')) {
            $model->attachObservation($observation, $context);
        }
    }

    public function addAudienceTagToLatest(
        Model $model,
        string $context,
        string $tag,
    ): void {
        if (! method_exists($model, 'latestObservation')) {
            return;
        }

        $latest = $model->latestObservation($context);

        if (! $latest) {
            return;
        }

        $tags = $this->uniqueTags([
            ...((array) ($latest->audience_tags ?? [])),
            $tag,
        ]);

        $latest->update([
            'audience_tags' => $tags,
        ]);
    }

    private function normalizeBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $body = trim($body);

        return $body === '' ? null : $body;
    }

    /**
     * @param  array<int, string>  $tags
     * @return array<int, string>
     */
    private function uniqueTags(array $tags): array
    {
        return array_values(array_unique(array_filter(Arr::flatten($tags))));
    }
}
