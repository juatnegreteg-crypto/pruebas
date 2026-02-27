<?php

namespace App\Models\Concerns;

use App\Models\Observation;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasObservations
{
    public function observations(): MorphToMany
    {
        return $this->morphToMany(Observation::class, 'observationable', 'observationables')
            ->withPivot('context')
            ->withTimestamps();
    }

    public function latestObservation(string $context, ?array $requiredTags = null): ?Observation
    {
        $query = $this->observations()
            ->wherePivot('context', $context)
            ->orderByDesc('observations.created_at');

        if ($requiredTags) {
            if ($this->getConnection()->getDriverName() === 'sqlite') {
                return $query
                    ->get()
                    ->first(function (Observation $observation) use ($requiredTags): bool {
                        $tags = (array) ($observation->audience_tags ?? []);

                        foreach ($requiredTags as $tag) {
                            if (! in_array($tag, $tags, true)) {
                                return false;
                            }
                        }

                        return true;
                    });
            }

            foreach ($requiredTags as $tag) {
                $query->whereJsonContains('audience_tags', $tag);
            }
        }

        return $query->first();
    }

    public function attachObservation(
        Observation $observation,
        string $context,
    ): void {
        $this->observations()->attach($observation->id, [
            'context' => $context,
        ]);
    }

    public function detachObservationsByContext(string $context): void
    {
        $this->observations()->wherePivot('context', $context)->detach();
    }
}
