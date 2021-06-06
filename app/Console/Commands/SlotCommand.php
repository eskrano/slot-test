<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class SlotCommand extends Command
{
    protected $name = 'slot';

    protected $description = 'Simulates slot machine';

    public function handle()
    {
        $bet = (int)abs($this->input->getOption('bet'));
        $rows = 3;
        $columns = 5;


        /**
         * Generate empty field of symbols first
         */
        $field = $this->generateEmptyField($rows, $columns);

        /**
         * Fill fields
         */
        foreach ($field as $row => $column_data) {
            foreach ($column_data as $col_key => $col_val) {
                $field[$row][$col_key] = $this->generateSymbol();
            }
        }


        /**
         * Then i will normalize field to normal look and define win lines
         */

        $normalized_field = collect($field)->flatten();

        /**
         * Win lines are hardcoded (followed by specs)
         */
        $win_lines = [
            [0, 3, 6, 9, 12],
            [1, 4, 7, 10, 13],
            [2, 5, 8, 11, 14],
            [0, 4, 8, 10, 12],
            [2, 4, 6, 10, 14]
        ];

        /**
         * Then I will check all my results and compare with win lines
         */

        $lines_winnings = [];

        foreach ($win_lines as $line_id => $win_line) {
            $win = false;
            $win_matches = 1;
            $first_elem = null;
            $line_broken = false;


            foreach ($win_line as $k_line_pos => $slot_pos) {
                if ($k_line_pos === 0) {
                    $first_elem = $normalized_field[$slot_pos];
                    continue;
                }

                if (false === $line_broken && $first_elem === $normalized_field[$slot_pos]) {
                    $win = true;
                    $win_matches++;
                }

                if (false === $line_broken && $first_elem !== $normalized_field[$slot_pos]) {
                    $line_broken = true;
                }

//                if (0 === $k_line_pos) {
//                    $next = $win_line[$k_line_pos + 1];
//
//                    if ($normalized_field[$slot_pos] === $normalized_field[$next]) {
//                        $win = true;
//                        $win_matches = 1;
//                    } else {
//                        continue;
//                    }
//                } elseif (count($win_line) - 1 === $k_line_pos) {
//                    /**
//                     * Latest pos just let check if we have 4 win matches and current slot is same as first - increase
//                     * win matches
//                     */
//
//                    if (
//                        4 === $win_matches &&
//                        $normalized_field[$slot_pos] === $normalized_field[$win_line[0]]
//                    ) {
//                        $win_matches++;
//                    }
//                } else {
//                    if (true === $win) {
//                        if (
//                            $normalized_field[$slot_pos] === $normalized_field[$win_line[0]] &&
//                            $normalized_field[$slot_pos - 1] === $normalized_field[$win_line[0]]
//                        ) {
//                            $win_matches++;
//                        } else {
//                            continue;
//                        }
//                    }
//                }
            }

            if (true === $win && $win_matches >= 3) {
                $lines_winnings[$line_id] = ['win_matches' => $win_matches];
            }
        }

        $total_win = 0;

        $paylines = [];
        if (count($lines_winnings) > 0) {

            foreach ($lines_winnings as $kline => $line_winning) {
                $total_win += $this->getWinCoefficient($line_winning['win_matches']) * $bet;
                $paylines[] = [implode(' ', $win_lines[$kline]) => $line_winning['win_matches']];
            }
        }

        /**
         * Prep final data
         */

        $board = $normalized_field;


        $bet_amount = $bet;


        $response = compact('board', 'paylines', 'bet_amount', 'total_win');

        /**
         * Will just dump response for debugging
         */

        dump($response);

        return json_encode($response, JSON_PRETTY_PRINT); // i don't know why but maybe it will be helpful

    }

    protected function getWinCoefficient(int $matches): float
    {
        $mapping_winning = [
            3 => 1.2,
            4 => 2,
            5 => 10,
        ];

        return $mapping_winning[$matches];
    }

    protected function generateSymbol(): string
    {
        $symbols_mapping = $this->getSymbolsMapping();
        //count($symbols_mapping) - 1
        $random_element = $symbols_mapping[random_int(0, count($symbols_mapping) - 1)];


        if (3 === $random_element['min_for_reward'] && random_int(1, 5) > 2) {
            return $this->generateSymbol();
        }
        return $random_element['name'];
    }


    protected function generateEmptyField(int $rows, int $columns): array
    {
        $field = [];

        for ($i = 0; $i < $rows; $i++) {
            /**
             * Generate rows then gen
             */
            $field[$i + 1] = [];

            for ($x = 1; $x < $columns + 1; $x++) {
                $field[$i + 1][] = null;
            }
        }

        return $field;
    }

    /**
     * Will be good to use common service to get symbols for each game, but right now it will be just hardcoded array
     * which returns symbols
     *
     * @return array
     */
    protected function getSymbolsMapping(): array
    {
        return [
            [
                'name' => '9',
                'multiplier' => 0.25,
                'min_for_reward' => 3,
            ],
            [
                'name' => '10',
                'multiplier' => 0.27,
                'min_for_reward' => 3,
            ],
            [
                'name' => 'J',
                'multiplier' => 0.3,
                'min_for_reward' => 3,
            ],
            [
                'name' => 'Q',
                'multiplier' => 0.5,
                'min_for_reward' => 3,
            ],
            [
                'name' => 'K',
                'multiplier' => 0.75,
                'min_for_reward' => 3,
            ],
            [
                'name' => 'A',
                'multiplier' => 0.85,
                'min_for_reward' => 3,
            ],
            [
                'name' => 'Cat',
                'multiplier' => 1.1,
                'min_for_reward' => 2,
            ],
            [
                'name' => 'Dog',
                'multiplier' => 1.3,
                'min_for_reward' => 2,
            ],
            [
                'name' => 'Monkey',
                'multiplier' => 1.5,
                'min_for_reward' => 2,
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            [
                'bet',
                null,
                InputOption::VALUE_OPTIONAL,
                'Bet amount in euro cents.',
                100
            ]
        ];
    }

}
