<?php

class Helpers
{
    // Define connection codes
    const CONNECTED = 1;
    const DISCONNECTED = 0;

    // Session variables
    const PLAYER_SESSION = "current_player";
    const LOGGED_IN = "logged_in";

    /**
     * Gets the address of the server
     * @return mixed
     */
    public static final function get_address()
    {
        $config = parse_ini_file("config.ini");
        $address = $config['address'];
        return $address;
    }

    /**
     * Establish connection to database
     * @return mysqli
     */
    public static final function get_connection()
    {
        // Load the config file
        $config = parse_ini_file("config.ini");

        // Setup the connection variables
        $username = $config['username'];
        $password = $config['password'];
        $server = $config['server'];
        $database = $config['database'];

        // Create connection using MySQLI object
        $connection = new mysqli(
            $server,
            $username,
            $password,
            $database
        );

        // PHP will die if there is no connection
        if ($connection->connect_error) {
            die ("Connection failed: " . $connection->connect_error);
        } else {
            return $connection;
        }
    }

    /**
     * Logs error message to file
     * @param $message
     */
    public static final function log_error_message($message)
    {
        $config = parse_ini_file("config.ini");
        $log_file = $config['logfile'];

        // Log the error
        error_log($message, 3, $log_file);
    }
}