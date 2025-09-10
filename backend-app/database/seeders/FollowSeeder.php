<?php

namespace Database\Seeders;

use App\Models\Follow\Follow;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FollowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Follow::query()->delete();

            $userIds = array_values(User::pluck('id')->all());
            $n = count($userIds);
            if ($n < 2) {
                return;
            }

            $minFriends = 30;
            $maxFriends = 40;

            $targets = [];
            foreach ($userIds as $uid) {
                $cap = max(0, min($maxFriends, $n - 1));
                $lo = max(0, min($minFriends, $cap));
                $targets[$uid] = ($lo > $cap) ? $cap : random_int($lo, $cap);
            }

            $degree = array_fill_keys($userIds, 0);
            $edges = [];
            $maxIterations = $n * 200;
            $iter = 0;

            while ($iter++ < $maxIterations) {
                $remaining = array_values(array_filter($userIds, fn ($u) => $degree[$u] < $targets[$u]));
                if (empty($remaining)) {
                    break;
                }

                usort($remaining, function ($a, $b) use ($degree, $targets) {
                    $da = $targets[$a] - $degree[$a];
                    $db = $targets[$b] - $degree[$b];

                    return $db <=> $da;
                });

                $u = $remaining[0];

                $candidates = array_values(array_filter($userIds, function ($v) use ($u, $degree, $targets, $edges) {
                    if ($v === $u) {
                        return false;
                    }
                    if ($degree[$v] >= $targets[$v]) {
                        return false;
                    }
                    $key = (min($u, $v)).'-'.(max($u, $v));

                    return ! isset($edges[$key]);
                }));

                if (empty($candidates)) {
                    break;
                }

                shuffle($candidates);
                $v = $candidates[0];
                $key = (min($u, $v)).'-'.(max($u, $v));
                if (! isset($edges[$key])) {
                    $edges[$key] = true;
                    $degree[$u]++;
                    $degree[$v]++;
                }
            }

            $finalPass = 0;
            while ($finalPass++ < 3) {
                $remaining = array_values(array_filter($userIds, fn ($u) => $degree[$u] < $targets[$u]));
                if (empty($remaining)) {
                    break;
                }

                foreach ($remaining as $u) {
                    if ($degree[$u] >= $targets[$u]) {
                        continue;
                    }

                    $candidates = array_values(array_filter($userIds, function ($v) use ($u, $degree, $targets, $edges) {
                        if ($v === $u) {
                            return false;
                        }
                        if ($degree[$v] >= $targets[$v]) {
                            return false;
                        }
                        $key = (min($u, $v)).'-'.(max($u, $v));

                        return ! isset($edges[$key]);
                    }));

                    if (empty($candidates)) {
                        continue;
                    }

                    shuffle($candidates);
                    $v = $candidates[0];
                    $key = (min($u, $v)).'-'.(max($u, $v));
                    if (! isset($edges[$key])) {
                        $edges[$key] = true;
                        $degree[$u]++;
                        $degree[$v]++;
                    }
                }
            }

            $now = now();
            $records = [];
            foreach (array_keys($edges) as $k) {
                [$a, $b] = array_map('intval', explode('-', $k, 2));
                $records[] = ['follower_id' => $a, 'followed_id' => $b, 'created_at' => $now, 'updated_at' => $now];
                $records[] = ['follower_id' => $b, 'followed_id' => $a, 'created_at' => $now, 'updated_at' => $now];
            }

            foreach (array_chunk($records, 1000) as $chunk) {
                DB::table('follows')->insertOrIgnore($chunk);
            }
        });
    }
}
