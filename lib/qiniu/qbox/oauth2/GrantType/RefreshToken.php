<?php

require_once(dirname(dirname(__FILE__)) . '/Exception.php');
require_once('IGrantType.php');

/**
 * Refresh Token  Parameters
 */
class QBox_OAuth_GrantType_RefreshToken implements QBox_OAuth_GrantType_IGrantType
{
    /**
     * Defines the Grant Type
     *
     * @var string  Defaults to 'refresh_token'.
     */
    const GRANT_TYPE = 'refresh_token';
    public $grant_type = self::GRANT_TYPE;

    /**
     * Adds a specific Handling of the parameters
     *
     * @return array of Specific parameters to be sent.
     * @param  mixed  $parameters the parameters array (passed by reference)
     */
    public function validateParameters(&$parameters)
    {
        if (!isset($parameters['refresh_token']))
        {
            throw new QBox_OAuth_InvalidArgumentException(
                'The \'refresh_token\' parameter must be defined for the refresh token grant type',
                QBox_OAuth_InvalidArgumentException::MISSING_PARAMETER
            );
        }
    }
}
