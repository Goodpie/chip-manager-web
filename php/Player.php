<?php

require_once(__DIR__ . '/../php/Helpers.php');

/**
 * Class Player
 *
 * Holds information and methods related specifically to the player
 *
 * @author GoodPie
 */
class Player
{
    private $id;
    private $username;
    private $chips;
    private $current_bid;
    private $chips_won;
    private $chips_lost;
    private $games_won;
    private $games_lost;

    /**
     * Default Constructor
     *
     * Initialized the player with the default values
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->username = "username";
        $this->chips = 0;
        $this->chips_won = 0;
        $this->chips_lost = 0;
        $this->games_won = 0;
        $this->games_lost = 0;
        $this->current_bid = 0; // Default current bid to 0
    }

    /**
     * Places a bid for the player
     *
     * Adds $amount to the current players bid and updates the database with that information
     *
     * @param   int $amount The amount the player wants to increment their current bid by
     * @return  bool            Whether the bid was placed successfully
     */
    public function place_bid($amount)
    {
        $connection = Helpers::get_connection();
        $bid_placed = false;

        if ($amount <= $this->chips && $amount > 0) {
            // Fetch the current values from the db to ensure we are up to date
            $select_success = false;
            $select_query = "SELECT current_bid, chips FROM player WHERE ID=$this->id LIMIT 1";

            // Fetch results from database
            $result = $connection->query($select_query);
            if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $select_success = true;

                // Update the local parameters to match database
                $difference = $amount - $this->current_bid;
                $this->chips = $this->chips - $difference;
                $this->current_bid = $amount;
            }

            if ($select_success) {
                // Update the database with the new values
                $update_query = "UPDATE player SET current_bid = ?, chips = ? WHERE ID=?";
                $update_statement = $connection->prepare($update_query);
                $update_statement->bind_param('iii', $this->current_bid, $this->chips, $this->id);
                $update_statement->execute();
                $update_statement->close();
                $bid_placed = true;
            }
        }

        // Close connection
        $connection->close();

        return $bid_placed;
    }

    /**
     * Resets the player bid
     *
     * Resets the players bid to 0 and adds that amount back to the players chips. Then updates
     * the database with this information
     */
    public function reset_bid()
    {
        // Establish connection
        $connection = Helpers::get_connection();

        $bid = -1;

        // First select the amount of bid
        $select_query = "SELECT current_bid FROM player WHERE ID=$this->id LIMIT 1";
        $selected_result = $connection->query($select_query);

        if ($row = $selected_result->fetch_array(MYSQLI_ASSOC)) {
            $bid = (int)$row['current_bid'];
        }

        // Now update the user to have 0 as current_bid
        $this->current_bid = 0;
        $this->chips += $bid;
        $update_query = "UPDATE player SET current_bid = ?, chips = chips + ? WHERE ID=$this->id";
        $statement = $connection->prepare($update_query);
        $statement->bind_param("ii", $this->current_bid, $bid);
        $statement->execute();

        // Close statement
        $statement->close();

        // Close connection
        $connection->close();
    }

    /**
     * Resets the players bid chips
     *
     * Removes the players current bid without returning them to their current chips
     *
     * @return int  The amount of chips that the player lost
     */
    public function remove_bid_chips()
    {
        // Establish connection
        $connection = Helpers::get_connection();

        $return_amount = 0;

        // First select the amount of bid
        $select_query = "SELECT current_bid FROM player WHERE ID=$this->id LIMIT 1";
        $selected_result = $connection->query($select_query);

        if ($row = $selected_result->fetch_array(MYSQLI_ASSOC)) {
            $return_amount = (int)$row['current_bid'];
        }

        // Now update the user to have 0 as current_bid
        $this->current_bid = 0;
        $update_query = "UPDATE player SET current_bid = ? WHERE ID=$this->id";
        $statement = $connection->prepare($update_query);
        $statement->bind_param("i", $this->current_bid);
        $statement->execute();

        // Close statement
        $statement->close();

        // Close connection
        $connection->close();

        // Player now needs updating
        $this->set_needs_update(1);

        // Return the amount of chips that were in the bid
        return $return_amount;
    }

    /**
     * Modifies the update status of the player
     *
     * @param int $needs_update
     */
    public function set_needs_update($needs_update)
    {
        $connection = Helpers::get_connection();

        // Update the status of needs update on player
        $query = "UPDATE `player` SET `needs_update`=? WHERE ID=?";
        $statement = $connection->prepare($query);
        $statement->bind_param('ii', $needs_update, $this->id);
        $statement->execute();
        $statement->close();

        $connection->close();
    }

    /**
     * Modifies the connection status of the player
     *
     * @param int $connected
     */
    public function set_connection($connected)
    {
        // Check that we have a valid parameter
        if ($connected == Helpers::CONNECTED || $connected == Helpers::DISCONNECTED) {
            // Establish connection
            $connection = Helpers::get_connection();

            // Prepare statement and execute
            $query = "UPDATE `player` SET `connected`=? WHERE ID=?";
            $statement = $connection->prepare($query);
            $statement->bind_param('ii', $connected, $this->id);
            $statement->execute();
            $statement->close();

            // Close connection
            $connection->close();
        }
    }

    /**
     * Gets common information about the player
     *
     * Returns the players:
     *
     * * username
     * * chips
     * * current_bid
     *
     * Only returns locally stored variables. Update may be required before calling this.
     *
     * @return string JSON encoded information about the player
     */
    public function get_simple_info()
    {
        // Return all relevant information about the player
        $player = array(
            'username' => $this->username,
            'chips' => $this->chips,
            'current_bid' => $this->current_bid
        );

        // Encode to JSON and return
        return json_encode($player);
    }

    /**
     * Gets all the information about the player
     *
     * Returns the players:
     *
     * * username
     * * chips
     * * current_bid
     * * chips_won
     * * chips_lost
     * * games_won
     * * games_lost
     *
     * Only returns locally stored variables. May require update before calling
     *
     * @return string
     */
    public function get_all_info()
    {
        // Return all relevant information about the player
        $player = array(
            'username' => $this->username,
            'chips' => $this->chips,
            'current_bid' => $this->current_bid,
            'chips_won' => $this->chips_won,
            'chips_lost' => $this->chips_lost,
            'games_won' => $this->games_won,
            'games_lost' => $this->games_lost
        );

        // Encode to JSON and return
        return $player;
    }

    /**
     * Update player information
     *
     * Checks if the player needs to be updated before fetching information from the player
     *
     * @return bool
     *
     */
    public function update()
    {
        $updated = false;
        if ($this->needs_update()) {

            $connection = Helpers::get_connection();

            // Fetch all player information
            $this->load_information();

            // Change status to does not require update
            $update_query = "UPDATE `player` SET `needs_update`=0 WHERE `ID`=?";
            $update_statement = $connection->prepare($update_query);
            $update_statement->bind_param('i', $this->id);
            $update_statement->execute();
            $update_statement->close();

            // Close connection
            $connection->close();

            $updated = true ;
            $this->set_needs_update(0);
        }

        return $updated;
    }

    /**
     * Checks if the player state need updating
     *
     * @return bool
     */
    public function needs_update()
    {
        // Establish connection
        $connection = Helpers::get_connection();

        $needs_updating = false;

        // Prepare and execute query
        $query = "SELECT needs_update FROM player WHERE ID=$this->id";

        // Parse the results
        $result = $connection->query($query);
        if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $needs_updating = $row['needs_update'];
        }

        // c3lose connection
        $connection->close();

        return $needs_updating;
    }

    /**
     * Loads player information
     *
     * Fetches the player information from the database and populates the class fields with
     * the updated player information
     *
     * @return bool Whether fetching the information was successful or not
     */
    public function load_information()
    {
        // Establish connection
        $connection = Helpers::get_connection();

        // Return boolean if successful
        $success = true;

        // Prepare the query and execute it
        $query = "SELECT * FROM player WHERE ID=$this->id LIMIT 1";

        // Get the results from the query
        $result = $connection->query($query);

        // If succeeded, fetch and parse the results
        if ($player_row = $result->fetch_array(MYSQLI_ASSOC)) {

            // Parse the results
            $this->username = $player_row['username'];
            $this->chips = $player_row['chips'];
            $this->current_bid = $player_row['current_bid'];
            $this->chips_won = $player_row['chips_won'];
            $this->chips_lost = $player_row['chips_lost'];
            $this->games_won = $player_row['games_won'];
            $this->games_lost = $player_row['games_lost'];
        } else {
            $success = false;
        }

        // Close the connection
        $connection->close();

        return $success;
    }

    /**
     * Check if the user is connected
     *
     * @return bool
     */
    public function is_connected()
    {
        // Establish connection
        $connection = Helpers::get_connection();

        $connected = false;

        // Prepare and execute query
        $query = "SELECT connected FROM player WHERE ID=$this->id LIMIT 1";

        // Parse the results
        $result = $connection->query($query);
        if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $connected = $row['connected'];
        }

        // Close connection
        $connection->close();

        return $connected;
    }

    /**
     * @return mixed
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_username(): string
    {
        return $this->username;
    }

    /**
     * @return int
     */
    public function get_chips(): int
    {
        return $this->chips;
    }

    /**
     * @return int
     */
    public function get_current_bid(): int
    {
        return $this->current_bid;
    }

    /**
     * @return int
     */
    public function get_chips_won(): int
    {
        return $this->chips_won;
    }

    /**
     * @return int
     */
    public function get_chips_lost(): int
    {
        return $this->chips_lost;
    }

    /**
     * @return int
     */
    public function get_games_won(): int
    {
        return $this->games_won;
    }

    /**
     * @return int
     */
    public function get_games_lost(): int
    {
        return $this->games_lost;
    }


}