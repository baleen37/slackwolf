<?php

/* Register the Composer autoloader */
use Slackwolf\Game;
use Slackwolf\GameRegistry;

require __DIR__ . '/vendor/autoload.php';

/* Load the dotenv package */
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

/* Initialise php stuff */
date_default_timezone_set(getenv('TIMEZONE'));

$loop = React\EventLoop\Factory::create();

$client = new Slack\RealTimeClient($loop);
$client->setToken(getenv('BOT_TOKEN'));

$registry = new GameRegistry();

$client->on('message', function ($data) use ($client, $registry) {
    $input = trim($data['text']);

    /* Handle DM's */
    if($data['channel'][0] == 'D') {
        if (! $registry->get()) {
            return false; // ignore dms if there are no games
        }

        $game = $registry->get();
        /** @var $game Game */

        // If we're in the setup phase
        if($game && $game->getState() == 'setup') {
            foreach ($game->getPlayers() as $player) {
                /** @var $player \Slack\User */

                // If the player DMing the bot is the Seer
                if($data['user'] == $player->getId() && $player->role == 'Seer') {
                    $foundPlayer = false;
                    // And the message contains one of the player Id's
                    foreach($game->getPlayers() as $player2) {
                        if(strpos($data['text'], $player2->getId()) !== false) {
                            $foundPlayer = true;

                            // Then thats the player the seer wants to See but seer can only see villagers and werewolves
                            if ($player2->role == 'Villager' || $player2->role == 'Seer') {
                                $client->getDMByUserId($player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($player2, $client) {
                                    $client->send($player2->getUsername().' is a Villager', $dm);
                                });
                            }
                            if ($player2->role == 'Werewolf') {
                                $client->getDMByUserId($player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($player2, $client) {
                                    $client->send($player2->getUsername().' is a Werewolf', $dm);
                                });
                            }

                            $game->setState('day'); // After the seer sees someone on the first night it becomes day

                            $client->getGroupById($game->getChannel())->then(function (\Slack\Channel $channel) use ($client) {
                                $client->send('The first night is over. The werewolves have moved into town and the seer has had a vision. Hunt down the werewolves.', $channel);
                            });

                            break;
                        }
                    }
                    if(!$foundPlayer) {
                        $client->getDMByUserId($player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($player, $client) {
                            $client->send('Sorry, didnt catch that.', $dm);
                        });
                    }
                }
            }
        }

        if($game && $game->getState() == 'night') {
            foreach ($game->getPlayers() as $player) {
                /** @var $player \Slack\User */

                // If the player DMing the bot is the Seer
                if($data['user'] == $player->getId() && $player->role == 'Seer') {
                    $foundPlayer = false;
                    // And the message contains one of the player Id's
                    foreach($game->getPlayers() as $player2) {
                        if(strpos($data['text'], $player2->getId()) !== false) {
                            $foundPlayer = true;

                            // Then thats the player the seer wants to See but seer can only see villagers and werewolves
                            if ($player2->role == 'Villager' || $player2->role == 'Seer') {
                                $client->getDMByUserId($player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($player2, $client) {
                                    $client->send($player2->getUsername().' is a Villager', $dm);
                                });
                            }
                            if ($player2->role == 'Werewolf') {
                                $client->getDMByUserId($player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($player2, $client) {
                                    $client->send($player2->getUsername().' is a Werewolf', $dm);
                                });
                            }

                            break;
                        }
                    }
                    if(!$foundPlayer) {
                        $client->getDMByUserId($player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($player, $client) {
                            $client->send('Sorry, didnt catch that.', $dm);
                        });
                    }
                }

                if($data['user'] == $player->getId() && $player->role == 'Werewolf') {
                    $foundPlayer = false;
                    // And the message contains one of the player Id's
                    foreach($game->getPlayers() as $player2) {
                        if(strpos($data['text'], $player2->getId()) !== false) {
                            if ($player2->role == 'Werewolf') {
                                $client->getDMByUserId($player->getId())->then(
                                    function (\Slack\DirectMessageChannel $dm) use ($player2, $client) {
                                        $client->send(
                                            $player2->getUsername() . ' is a Werewolf. Cant kill each other silly.',
                                            $dm
                                        );
                                    }
                                );
                                break;
                            }

                            $foundPlayer = true;

                            // Save the vote
                            if($game->hasAlreadyVoted($player->getId())) {
                                $client->getDMByUserId($player->getId())->then(
                                    function (\Slack\DirectMessageChannel $dm) use ($client) {
                                        $client->send("You've already voted. Wait for the other werewolves...",$dm);
                                    }
                                );

                                return false;
                            } else {
                                $players = $game->getPlayers();
                                $game->addVote($player2->getId(), $players[$data['user']]);

                                $pretty_votes = "Nighttime Lynch Vote\r\n--------------------\r\n";

                                $votes = $game->getVotes();

                                $users_who_voted = [];
                                $remaining_voters = [];

                                foreach ($votes as $lynch_id => $voters) {
                                    $pretty_votes .= "Kill " . $players[$lynch_id]->getUsername() . ': Votes: [';
                                    foreach ($voters as $user) {
                                        $pretty_votes .= $user->getUsername() . ', ';
                                        $users_who_voted[$user->getId()] = $user->getId();
                                    }
                                    $pretty_votes = rtrim($pretty_votes, ', ');
                                    $pretty_votes .= "]\r\n";
                                }

                                $pretty_votes .= "Remaining Voters: ";
                                foreach ($game->getPlayers() as $game_player) {
                                    if ($game_player->role == 'Werewolf') {
                                        if (!isset($users_who_voted[$game_player->getId()])) {
                                            $remaining_voters[] = $game_player->getId();
                                            $pretty_votes .= $game_player->getUsername() . ', ';
                                        }
                                    }
                                }
                                $pretty_votes = rtrim($pretty_votes, ', ');

                                foreach ($players as $game_player) {
                                    if ($game_player->role == 'Werewolf') {
                                        $client->getDMByUserId($game_player->getId())->then(
                                            function (\Slack\DirectMessageChannel $dm) use ($client, $pretty_votes) {
                                                $client->send($pretty_votes, $dm);
                                            }
                                        );
                                    }
                                }

                                if (count($remaining_voters) > 0) {
                                    return false;
                                }

                                /* See who gets lynched */
                                $votes = $game->getVotes();

                                if (count($votes) > 1) {
                                    // Then it wasn't unanimous
                                    $game->clearVotes();

                                    foreach ($players as $game_player) {
                                        if ($game_player->role == 'Werewolf') {
                                            $client->getDMByUserId($game_player->getId())->then(
                                                function (\Slack\DirectMessageChannel $dm) use ($client) {
                                                    $client->send(
                                                        "The werewolves must reach a unanimous decision. Please vote again.",
                                                        $dm
                                                    );
                                                }
                                            );
                                        }
                                    }

                                    return false;
                                }

                                foreach ($votes as $lynch_id => $voters) {
                                    $game->removePlayer($lynch_id);

                                    $pretty_lynch_text = "The werewolves have mauled " . $players[$lynch_id]->getUsername(
                                        ) . "(" . $players[$lynch_id]->role . ") to death.";

                                    $client->getGroupById($game->getChannel())->then(
                                        function (\Slack\Channel $channel) use ($client, $pretty_lynch_text) {
                                            /**@var $player \Slack\User */
                                            $client->send($pretty_lynch_text, $channel);
                                        }
                                    );

                                    if ($game->isOver()) {
                                        $winning_team = $game->whoWon();

                                        $client->getGroupById($game->getChannel())->then(
                                            function (\Slack\Channel $channel) use ($client, $winning_team) {
                                                /**@var $player \Slack\User */
                                                $client->send(
                                                    "The game is over. The {$winning_team} are victorious!",
                                                    $channel
                                                );
                                            }
                                        );

                                        $registry->clear();

                                        return false;
                                    }

                                    $game->setState('day');
                                    $game->clearVotes();

                                    $client->getGroupById($game->getChannel())->then(
                                        function (\Slack\Channel $channel) use ($client) {
                                            /**@var $player \Slack\User */
                                            $client->send("The sun rises and the village wakes up...", $channel);
                                        }
                                    );
                                }
                            }

                            break;
                        }
                    }
                    if(!$foundPlayer) {
                        $client->getDMByUserId($player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($player, $client) {
                            $client->send('Sorry, didnt catch that.', $dm);
                        });
                    }
                }
            }
        }

        return false;
    }

    if($input[0] !== '!') {
        return false;
    }

    if($input == '!leave') {
        $client->disconnect();
    }

    $input_array = explode(' ', $input);

    $game = $registry->get();
    /** @var $game Game */

    if ($input_array[0] == '!vote' && $game && $game->getState() == 'day') {
        if ( ! $registry->exists()) {
            return false;
        }

        $players = $game->getPlayers();

        if(! isset($players[$data['user']])) {
            return false;
        }

        if ( ! isset($input_array[1])) {
            $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($players,$data,$game,$client) {
                $player = $players[$data['user']];
                /**@var $player \Slack\User */
                $client->send('@'.$player->getUsername().': Sorry, didnt catch that', $channel);
            });

            return false;
        }

        $target = $input_array[1];


        $target_found = false;

        foreach($game->getPlayers() as $player) {
            /** @var $player \Slack\User */
            if(strpos($target, $player->getId()) !== false) {
                $target_found = true;

                if ($game->hasAlreadyVoted($data['user'])) {
                    $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($players,$data,$game,$client) {
                        $msgPlayer = $players[$data['user']];
                        /**@var $msgPlayer \Slack\User */
                        $client->send('@'.$msgPlayer->getUsername().': Sorry, you already voted.', $channel);
                    });
                } else {
                    $game->addVote($player->getId(), $players[$data['user']]);

                    $votes = $game->getVotes();

                    $pretty_votes = "Town Lynch Ballot\r\n-------\r\n";

                    $users_who_voted = [];
                    $remaining_voters = [];

                    foreach ($votes as $lynch_id => $users) {
                        $lynch_player = $players[$lynch_id];

                        $pretty_votes .= "Kill " . $lynch_player->getUsername() . " Votes: [";

                        foreach($users as $user) {
                            $pretty_votes .= $user->getUsername().', ';

                            $users_who_voted[$user->getId()] = $user->getId();
                        }

                        $pretty_votes = rtrim($pretty_votes, ', ');

                        $pretty_votes .= "]\r\n";
                    }

                    $pretty_votes .= "Remaining Voters: ";
                    foreach($game->getPlayers() as $game_player) {
                        if( ! isset($users_who_voted[$game_player->getId()])) {
                            $remaining_voters[] = $game_player->getId();
                            $pretty_votes .= $game_player->getUsername() . ', ';
                        }
                    }
                    $pretty_votes = rtrim($pretty_votes, ', ');

                    $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($pretty_votes,$player,$data,$game,$client) {
                        /**@var $player \Slack\User */
                        $client->send($pretty_votes, $channel);
                    });

                    if (count($remaining_voters) > 0) {
                        return false;
                    }

                    /* See who gets lynched */
                    $vote_count = [];

                    $votes = $game->getVotes();

                    foreach ($votes as $lynch_player_id => $voters) {
                        if ( ! isset($vote_count[$lynch_player_id])) {
                            $vote_count[$lynch_player_id] = 0;
                        }

                        $vote_count[$lynch_player_id] += count($voters);
                    }

                    $players_to_be_lynched = [];

                    $max = 0;
                    foreach ($vote_count as $lynch_player_id => $num_votes) {
                        if ($num_votes > $max) {
                            $max = $num_votes;
                        }
                    }
                    foreach ($vote_count as $lynch_player_id => $num_votes) {
                        if ($num_votes == $max) {
                            $players_to_be_lynched[] = $lynch_player_id;
                        }
                    }

                    $pretty_lynch_text = "The town has killed: ";

                    foreach($players_to_be_lynched as $player_id) {
                        $player = $players[$player_id];
                        $pretty_lynch_text .= $player->getUsername().' ('.$player->role.'), ';
                        $game->removePlayer($player_id);
                    }
                    $pretty_lynch_text = rtrim($pretty_lynch_text, ', ');

                    $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($client, $pretty_lynch_text) {
                        /**@var $player \Slack\User */
                        $client->send($pretty_lynch_text, $channel);
                    });

                    if($game->isOver()) {
                        $winning_team = $game->whoWon();

                        $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($client, $winning_team) {
                            /**@var $player \Slack\User */
                            $client->send("The game is over. The {$winning_team} are victorious!", $channel);
                        });

                        $registry->clear();

                        return false;
                    }

                    $game->setState('night');
                    $game->clearVotes();

                    $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($client) {
                        /**@var $player \Slack\User */
                        $client->send("The sun beings to set and the villagers fall asleep...", $channel);
                    });

                    $remaining_players = [];

                    foreach ($game->getPlayers() as $game_player) {
                        $remaining_players[] = $game_player->getUsername();
                    }

                    $remaining_players = implode(',', $remaining_players);

                    foreach($game->getPlayers() as $game_player) {
                        /** @var $player \Slack\User */
                        if($game_player->role=='Seer') {
                            $client->getDMByUserId($game_player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($client, $remaining_players) {
                                $client->send("Its nighttime. Who would you like to See? (type a username ex: @chris)\r\nRemaining Players: {$remaining_players}", $dm);
                            });
                        }
                        if($game_player->role=='Werewolf') {
                            $client->getDMByUserId($game_player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($client, $remaining_players) {
                                $client->send("Werewolf, who would you like to kill during the night? (type a username ex: @chris)\r\nRemaining Players: {$remaining_players}", $dm);
                            });
                        }
                    }
                }
                break;
            }
        }

        if ( ! $target_found) {
            $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($players,$data,$game,$client) {
                $player = $players[$data['user']];
                /**@var $player \Slack\User */
                $client->send('@'.$player->getUsername().': Sorry, didnt catch that.', $channel);
            });
        }

        return false;
    }

    if ($input_array[0] == '!start') {
        if($registry->exists()) {
            $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($client) {
                $client->send('A game is already in progress.', $channel);
            });

            return false;
        }

        $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($client, $registry, $data) {
            $channel->getMembers()->then(function($users) use($registry,$data,$client,$channel){
                $game = new Game($data['channel'], $users);
                $registry->set($game);

                $roles = [];

                foreach ($game->getPlayers() as $player) {
                    $roles[] = $player->role;
                }

                $roles = implode(',', $roles);

                $client->send("Starting a new game with roles: {$roles}\r\nWhen the Seer has a vision, the game may begin.", $channel);

                foreach($game->getPlayers() as $player) {
                    /** @var $player \Slack\User */
                    $client->getDMByUserId($player->getId())->then(function(\Slack\DirectMessageChannel $dm) use ($player, $client) {
                        $client->send('Your role is: ' . $player->role, $dm);

                        if ($player->role == 'Seer') {
                            $client->send('Seer, who would you like to See? (type a username ex: @chris)', $dm);
                        }
                    });
                }
            });
        });
    }

    if ($input_array[0] == '!end') {
        if ( ! $registry->exists()) {
            $client->getGroupById($data['channel'])->then(
                function (\Slack\Channel $channel) use ($client) {
                    $client->send('There is no game in progress.', $channel);
                }
            );

            return false;
        }

        $registry->clear();

        $client->getGroupById($data['channel'])->then(function (\Slack\Channel $channel) use ($client) {
            $client->send('Game has been ended!', $channel);
        });
    }

    echo $input."\n";
});

$client->connect()->then(function() {
    echo "Connected\n";
});

$loop->run();