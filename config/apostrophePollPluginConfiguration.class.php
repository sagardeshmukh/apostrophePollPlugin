<?php

/**
 * apostrophePollPlugin configuration.
 * 
 * @package     apostrophePollPlugin
 * @subpackage  config
 * @author     Raffaele Bolliger <raffaele.bolliger at gmail.com>
 * @version     SVN: $Id: PluginConfiguration.class.php 17207 2009-04-10 15:36:26Z Kris.Wallsmith $
 */
class apostrophePollPluginConfiguration extends sfPluginConfiguration {

    const VERSION = '1.0.0-DEV';

    static $registered = false;

    /**
     * @see sfPluginConfiguration
     */
    public function initialize() {


        if (!self::$registered) {

            // Routes for various admin modules
            if (sfConfig::get('app_a_admin_routes_register', true)) {
                $this->dispatcher->connect('routing.load_configuration', array('aPollRouting', 'listenToRoutingAdminLoadConfigurationEvent'));
            }

            // adding global button
             $this->dispatcher->connect('a.getGlobalButtons', array(get_class($this), 'getGlobalButtons'));
            
             
             // parsing request parameters when submitting a poll
             $this->dispatcher->connect('apoll.filter_submit_poll_request_parameters', array('aPollBaseForm','listenToRequestParametersFilterEvent'));
             
            self::$registered = true;
        }
    }

    static public function getGlobalButtons() {
        $user = sfContext::getInstance()->getUser();

        if ($user->hasCredential('admin')) {
            aTools::addGlobalButtons(array(
                new aGlobalButton('polls', 'Polls', '@a_poll_poll_admin', 'a-poll'),
            ));
        }
    }

}
