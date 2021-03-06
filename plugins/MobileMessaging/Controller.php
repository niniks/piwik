<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package MobileMessaging
 */

namespace Piwik\Plugins\MobileMessaging;

use Piwik\Piwik;
use Piwik\Common;
use Piwik\IP;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\Plugins\MobileMessaging\API;
use Piwik\View;
use Piwik\Plugins\MobileMessaging\CountryCallingCodes;
use Piwik\Plugins\MobileMessaging\SMSProvider;

require_once PIWIK_INCLUDE_PATH . '/plugins/UserCountry/functions.php';

/**
 *
 * @package MobileMessaging
 */
class Controller extends \Piwik\Controller\Admin
{
    /*
     * Mobile Messaging Settings tab :
     *  - set delegated management
     *  - provide & validate SMS API credential
     *  - add & activate phone numbers
     *  - check remaining credits
     */
    public function index()
    {
        Piwik::checkUserIsNotAnonymous();

        $view = new View('@MobileMessaging/index');

        $view->isSuperUser = Piwik::isUserIsSuperUser();

        $mobileMessagingAPI = API::getInstance();
        $view->delegatedManagement = $mobileMessagingAPI->getDelegatedManagement();
        $view->credentialSupplied = $mobileMessagingAPI->areSMSAPICredentialProvided();
        $view->accountManagedByCurrentUser = $view->isSuperUser || $view->delegatedManagement;
        $view->strHelpAddPhone = Piwik_Translate('MobileMessaging_Settings_PhoneNumbers_HelpAdd', array(Piwik_Translate('UserSettings_SubmenuSettings'), Piwik_Translate('MobileMessaging_SettingsMenu')));
        if ($view->credentialSupplied && $view->accountManagedByCurrentUser) {
            $view->provider = $mobileMessagingAPI->getSMSProvider();
            $view->creditLeft = $mobileMessagingAPI->getCreditLeft();
        }

        $view->smsProviders = SMSProvider::$availableSMSProviders;

        // construct the list of countries from the lang files
        $countries = array();
        foreach (Common::getCountriesList() as $countryCode => $continentCode) {
            if (isset(CountryCallingCodes::$countryCallingCodes[$countryCode])) {
                $countries[$countryCode] =
                    array(
                        'countryName'        => \Piwik\Plugins\UserCountry\countryTranslate($countryCode),
                        'countryCallingCode' => CountryCallingCodes::$countryCallingCodes[$countryCode],
                    );
            }
        }
        $view->countries = $countries;

        $view->defaultCountry = Common::getCountry(
            LanguagesManager::getLanguageCodeForCurrentUser(),
            true,
            IP::getIpFromHeader()
        );

        $view->phoneNumbers = $mobileMessagingAPI->getPhoneNumbers();

        $this->setBasicVariablesView($view);

        echo $view->render();
    }
}
