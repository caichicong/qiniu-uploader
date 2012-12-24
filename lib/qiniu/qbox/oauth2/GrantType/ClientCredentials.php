<?php

require_once('IGrantType.php');

/**
 * Client Credentials Parameters
 */
class QBox_OAuth_GrantType_ClientCredentials implements QBox_OAuth_GrantType_IGrantType
{
    /**
     * Defines the Grant Type
     *
     * @var string  Defaults to 'client_credentials'.
     */
    const GRANT_TYPE = 'client_credentials';
    public $grant_type = self::GRANT_TYPE;

    /**
     * Adds a specific Handling of the parameters
     *
     * @return array of Specific parameters to be sent.
     * @param  mixed  $parameters the parameters array (passed by reference)
     */
    public function validateParameters(&$parameters)
    {
    }
}
