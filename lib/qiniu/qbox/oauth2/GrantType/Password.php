<?php

require_once(dirname(dirname(__FILE__)) . '/Exception.php');
require_once('IGrantType.php');

/**
 * Password Parameters
 */
class QBox_OAuth_GrantType_Password implements QBox_OAuth_GrantType_IGrantType
{
    /**
     * Defines the Grant Type
     *
     * @var string  Defaults to 'password'.
     */
    const GRANT_TYPE = 'password';
    public $grant_type = self::GRANT_TYPE;

    /**
     * Adds a specific Handling of the parameters
     *
     * @return array of Specific parameters to be sent.
     * @param  mixed  $parameters the parameters array (passed by reference)
     */
    public function validateParameters(&$parameters)
    {
        if (!isset($parameters['username']))
        {
            throw new QBox_OAuth_InvalidArgumentException(
                'The \'username\' parameter must be defined for the Password grant type',
                QBox_OAuth_InvalidArgumentException::MISSING_PARAMETER
            );
        }
        elseif (!isset($parameters['password']))
        {
            throw new QBox_OAuth_InvalidArgumentException(
                'The \'password\' parameter must be defined for the Password grant type',
                QBox_OAuth_InvalidArgumentException::MISSING_PARAMETER
            );
        }
    }
}
