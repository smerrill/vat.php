<?php
namespace DvK\Vat;

/**
 * Class Validator
 *
 * @package DvK\Vat
 */
class Validator {

    /**
     * Regular expression patterns per country code
     *
     * @var array
     * @link http://ec.europa.eu/taxation_customs/vies/faq.html?locale=lt#item_11
     */
    protected static $patterns = array(
        'AT' => 'U[A-Z\d]{8}',
        'BE' => '(0\d{9}|\d{10})',
        'BG' => '\d{9,10}',
        'CY' => '\d{8}[A-Z]',
        'CZ' => '\d{8,10}',
        'DE' => '\d{9}',
        'DK' => '(\d{2} ?){3}\d{2}',
        'EE' => '\d{9}',
        'EL' => '\d{9}',
        'ES' => '[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8}',
        'FI' => '\d{8}',
        'FR' => '([A-Z]{2}|\d{2})\d{9}',
        'GB' => '\d{9}|\d{12}|(GD|HA)\d{3}',
        'HR' => '\d{11}',
        'HU' => '\d{8}',
        'IE' => '[A-Z\d]{8}|[A-Z\d]{9}',
        'IT' => '\d{11}',
        'LT' => '(\d{9}|\d{12})',
        'LU' => '\d{8}',
        'LV' => '\d{11}',
        'MT' => '\d{8}',
        'NL' => '\d{9}B\d{2}',
        'PL' => '\d{10}',
        'PT' => '\d{9}',
        'RO' => '\d{2,10}',
        'SE' => '\d{12}',
        'SI' => '\d{8}',
        'SK' => '\d{10}'
    );

    /**
     * Regular expression patterns per non-EU country code
     *
     * These must have any text still present because most do not match their country codes.
     *
     * @var array
     * @link https://en.wikipedia.org/wiki/VAT_identification_number
     */
    protected static $nonEuPatterns = array(
        // 'AE' => '', // Couldn't find a reference that matches anything we have on file
        'AR' => '(CUIT)?\d{11}',
        'AU' => '(ABN)?\d{11}',  // We could later add modulo 89 validation as per https://abr.business.gov.au/Help/AbnFormat
        'BR' => '(CNPJ|CPF)?(\d{14}|\d{11})',
        'CA' => '(BN|NE)?\d{9}',
        'CH' => '((CH)?\d{6}|(CHE)?\d{9})(TVA|MWST|IVA)?', // For CHE numbers "the last digit is a MOD11 checksum digit build with weighting pattern: 5,4,3,2,7,6,5,4"
        'GR' => '(EL|GR)?\d{9}',
        'IL' => '(IL)?\d{9}',
        // 'IN' => '', // None of the format strings I could find online match anything we have for India
        // 'JO' => '', // Couldn't find a reference that matches anything we have on file
        // 'LB' => '', // Couldn't find a reference that matches anything we have on file
        // 'LI' => '', // Couldn't find a reference that matches anything we have on file
        // 'MX' => '', // Couldn't find a reference that matches anything we have on file
        'NO' => '(Orgnr)?\d{9}(MVA)?',
        'PE' => '(RUC)?\d{11}',
        'RU' => '(ИНН)?(\d{10}|\d{12})',
        // 'SG' => '', // Couldn't find a reference that matches anything we have on file
        // 'TH' => '', // Couldn't find a reference that matches anything we have on file
        'TR' => '(TR)?\d{10}',
        // 'ZA' => '', // Couldn't find a reference that matches anything we have on file
    );

    /**
     * VatValidator constructor.
     *
     * @param Vies\Client $client        (optional)
     */
    public function __construct( Vies\Client $client = null ) {
        $this->client = $client;

        if( ! $this->client ) {
            $this->client = new Vies\Client();
        }
    }

    /**
     * Can the VAT format be checked by our library?
     *
     * @param string $country
     *
     * @return boolean
     */
    public function canCheckCountryFormat( $country ) {
        return array_key_exists( $country, self::$patterns ) || array_key_exists( $country, self::$nonEuPatterns );
    }

    /**
     * Validate a VAT number format. This does not check whether the VAT number was really issued.
     *
     * @param string $vatNumber
     * @param string $country
     *
     * @return boolean
     */
    public function validateFormat( $vatNumber, $country = NULL ) {
        $vatNumber = strtoupper( $vatNumber );
        if ( is_null( $country) ) {
            $country = substr( $vatNumber, 0, 2 );
        }
        $number = substr( $vatNumber, 2 );

        if( isset( self::$patterns[$country]) ) {
            $matches = preg_match( '/^' . self::$patterns[$country] . '$/', $number ) > 0;
            return $matches;
        } elseif( isset( self::$nonEuPatterns[$country]) ) {
            $matches = preg_match( '/^' . self::$nonEuPatterns[$country] . '$/', $number ) > 0;
            return $matches;
        }

        return false;
    }

    /**
     *
     * @param string $vatNumber
     *
     * @return boolean
     *
     * @throws Vies\ViesException
     */
    public function validateExistence($vatNumber) {
        $vatNumber = strtoupper( $vatNumber );
        $country = substr( $vatNumber, 0, 2 );
        $number = substr( $vatNumber, 2 );
        return $this->client->checkVat($country, $number);
    }

    /**
     * Validates a VAT number using format + existence check.
     *
     * @param string $vatNumber Either the full VAT number (incl. country) or just the part after the country code.
     * @param string $country
     *
     * @return boolean
     *
     * @throws Vies\ViesException
     */
    public function validate( $vatNumber, $country = NULL ) {
       return $this->validateFormat( $vatNumber, $country ) && $this->validateExistence( $vatNumber );
    }


}
