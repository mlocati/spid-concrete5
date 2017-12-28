<?php

namespace SPID\Attributes;

use DateTime;

/**
 * @see http://www.agid.gov.it/sites/default/files/regole_tecniche/tabella_attributi_idp_v1_0.pdf
 */
class SpidAttributes
{
    /**
     * Attribute ID: SPID ID.
     *
     * @var string
     *
     * @example ABCD123456789A Where 'ABCD' (4 chars) is assigned to the identity provider, '123456789A' (10 chars) is assigned by the identity provider.
     */
    const ID_SPIDCODE = 'spidCode';

    /**
     * Attribute ID: first name(s).
     *
     * @var string
     *
     * @example Anna Maria
     */
    const ID_NAME = 'name';

    /**
     * Attribute ID: last name.
     *
     * @var string
     *
     * @example Kennedy
     */
    const ID_FAMILYNAME = 'familyName';

    /**
     * Attribute ID: Italian land registry code of the place of birth.
     *
     * @var string
     *
     * @example A794
     *
     * @see https://sister.agenziaentrate.gov.it/CitizenArCom/index.jsp
     */
    const ID_BIRTH_PLACE = 'placeOfBirth';

    /**
     * Attribute ID: code of the Italian county of birth.
     *
     * @var string
     *
     * @example BG
     *
     * @see http://www.aci.it/i-servizi/normative/codice-della-strada/elenco-sigle-province-ditalia.html
     */
    const ID_BIRTH_COUNTY = 'countyOfBirth';

    /**
     * Attribute ID: birth date in YYYY-MM-DD format.
     *
     * @var string
     *
     * @example 1974-12-31
     */
    const ID_BIRTH_DATE = 'dateOfBirth';

    /**
     * Attribute ID: gender (F or M).
     *
     * @var string
     *
     * @example F
     */
    const ID_GENDER = 'gender';

    /**
     * Attribute ID: company name.
     *
     * @var string
     *
     * @example Agenzia per l'Italia Digitale
     */
    const ID_COMPANYNAME = 'companyName';

    /**
     * Attribute ID: legal address.
     *
     * @var string
     *
     * @example via Listz 21 00144 Roma
     */
    const ID_REGISTEREDOFFICE = 'registeredOffice';

    /**
     * Attribute ID: fiscal number.
     *
     * @var string
     *
     * @example TINIT-CCCNNN64T30H501H
     *
     * @see http://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf
     */
    const ID_FISCALNUMBER = 'fiscalNumber';

    /**
     * Attribute ID: VAT code.
     *
     * @var string
     *
     * @example VATIT-12345678901
     *
     * @see http://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf
     */
    const ID_VATCODE = 'ivaCode';

    /**
     * Attribute ID: ID card.
     * Space-separated fields:
     * - document type ('cartaIdentita', 'passaporto', 'patenteGuida', 'patenteNautica', 'librettoPensione', 'patentinoImpTermici', 'portoArmi', 'tesseraRiconoscimento')
     * - document number
     * - issuer institution (examples: 'regioneLazio', 'provinciaCatania', 'prefetturaRoma', 'MinisteroEconomiaFinanze')
     * - issue date (YYYY-MM-DD)
     * - expiration date (YYYY-MM-DD).
     *
     * @var string
     *
     * @example CartaIdentita AS09452389 ComuneRoma 2013-01-02 2013-01-31
     */
    const ID_IDCARD = 'idCard';

    /**
     * Attribute ID: mobile phone.
     *
     * @var string
     *
     * @example 34912345678
     */
    const ID_MOBILEPHONE = 'mobilePhone';

    /**
     * Attribute ID: email address.
     *
     * @var string
     *
     * @example email@example.com
     */
    const ID_EMAIL = 'email';

    /**
     * Attribute ID: street address.
     *
     * @var string
     *
     * @example via Listz 21 00144 Roma
     */
    const ID_ADDRESS = 'address';

    /**
     * Attribute ID: identity expiration date.
     *
     * @var string
     *
     * @example 2099-12-31
     */
    const ID_EXPIRATIONDATE = 'expirationDate';

    /**
     * Attribute ID: PEC email address.
     *
     * @var string
     *
     * @example pec@example.com
     */
    const ID_DIGITALADDRESS = 'digitalAddress';

    /**
     * Return the defaild SPID attributes.
     *
     * @return array Keys are the attribute identifiers, values are the attribute names
     */
    public function getAttributes()
    {
        return [
            static::ID_SPIDCODE => tc('SPID Attribute', 'SPID code'),
            static::ID_NAME => tc('SPID Attribute', 'Name'),
            static::ID_FAMILYNAME => tc('SPID Attribute', 'Family name'),
            static::ID_BIRTH_PLACE => tc('SPID Attribute', 'Place of birth'),
            static::ID_BIRTH_COUNTY => tc('SPID Attribute', 'County of birth'),
            static::ID_BIRTH_DATE => tc('SPID Attribute', 'Date of birth'),
            static::ID_GENDER => tc('SPID Attribute', 'Gender'),
            static::ID_COMPANYNAME => tc('SPID Attribute', 'Company name'),
            static::ID_REGISTEREDOFFICE => tc('SPID Attribute', 'Legal address'),
            static::ID_FISCALNUMBER => tc('SPID Attribute', 'Fiscal number'),
            static::ID_VATCODE => tc('SPID Attribute', 'VAT code'),
            static::ID_IDCARD => tc('SPID Attribute', 'ID Card'),
            static::ID_MOBILEPHONE => tc('SPID Attribute', 'Mobile phone'),
            static::ID_EMAIL => tc('SPID Attribute', 'Email'),
            static::ID_ADDRESS => tc('SPID Attribute', 'Address'),
            static::ID_EXPIRATIONDATE => tc('SPID Attribute', 'Expiration date'),
            static::ID_DIGITALADDRESS => tc('SPID Attribute', 'Email (PEC)'),
        ];
    }

    /**
     * Get the name of an attribute given its ID.
     *
     * @param string $id
     *
     * @return string
     */
    public function getAttributeName($id)
    {
        $names = $this->getAttributes();

        return isset($names[$id]) ? $names[$id] : $id;
    }

    /**
     * @param string $id
     * @param string|mixed $value
     *
     * @return mixed
     */
    public function normalizeAttributeValue($id, $value)
    {
        switch ($id) {
            case static::ID_SPIDCODE:
            case static::ID_NAME:
            case static::ID_FAMILYNAME:
            case static::ID_BIRTH_PLACE:
            case static::ID_BIRTH_COUNTY:
            case static::ID_GENDER:
            case static::ID_COMPANYNAME:
            case static::ID_REGISTEREDOFFICE:
            case static::ID_FISCALNUMBER:
            case static::ID_VATCODE:
            case static::ID_IDCARD:
            case static::ID_MOBILEPHONE:
            case static::ID_EMAIL:
            case static::ID_ADDRESS:
            case static::ID_DIGITALADDRESS:
                $value = is_string($value) ? trim($value) : '';
                $result = $value === '' ? null : $value;
                break;
            case static::ID_BIRTH_DATE:
            case static::ID_EXPIRATIONDATE:
                $value = is_string($value) ? trim($value) : '';
                if ($value !== '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
                    $result = new DateTime($value);
                } else {
                    $result = null;
                }
                break;
            default:
                $result = $value;
                break;
        }

        return $result;
    }
}
