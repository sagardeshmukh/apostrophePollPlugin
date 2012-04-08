<?php

/**
 * Checks that a poll item configured in app.yml satisfies all requirements:
 *  * Definition of poll item in app.yml
 *  * Availability of form class
 *  * Form class extends aPollBaseForm
 * 
 *
 * @author Raffaele Bolliger <raffaele.bolliger at gmail.com>
 */
class aPollValidatorPollItem extends sfValidatorBase {

    /**
     * Configures the current validator.
     * This method allows each validator to add options and error messages during validator creation.
     * Available options:
     *  * max: The maximum value allowed
     *  * min: The minimum value allowed
     * Available error codes:
     *  * max
     *  * min
     *
     * @param array $options   An array of options
     * @param array $messages  An array of error messages
     * @see sfValidatorBase
     */
    protected function configure($options = array(), $messages = array()) {

        $this->addRequiredOption('poll_items');

        $this->addMessage('form_field', 'Field "form" is not defined in %poll%.');
        $this->addMessage('form_class', 'Class %form% defined in %poll% does not exist or is not autoloaded. Place it in a lib directory.');
        $this->addMessage('form_extends', 'Class %form% defined in %poll% does not extend aPollBaseForm.');
        $this->addMessage('view_template', 'The partial "%partial%" defined in "view_template" field cannot be found.');
        $this->addMessage('submit_action', 'The action "%action%" defined in the "submit_action" field cannot be found.');
        $this->addMessage('submit_success_template', 'The template "%template%" defined in the "submit_success_template" field cannot be found.');
        $this->addMessage('multiple_submissions', 'Field "allow_multiple_submissions" only accepts "true" and "false" as values.');
        $this->addMessage('cookie_lifetime', 'The content of "cookie_lifetime" must be a valid number, in seconds.');
        $this->addMessage('send_notification', 'Fields "send_notification" and "do_send" only accepts "true" and "false".');
        $this->addMessage('send_email', 'Fields "%field%" and "%global_field%" must define a valid email or a valid user.');
        $this->addMessage('email_title', 'The partial "%partial%" defined in "email_title_template" field cannot be found.');
        $this->addMessage('email_body', 'The partial "%partial%" defined in "email_body_template" field cannot be found.');
    }

    /**
     * Cleans the input value.
     *
     * @param  mixed $value  The input value
     * @return mixed The cleaned value
     * @throws sfValidatorError
     */
    protected function doClean($value) {

        $polls = $this->getOption('poll_items');
        $poll_app = 'app_aPoll_available_polls_' . $value;

        // checks that the item exists
        if (!isset($polls[$value])) {
            throw new sfValidatorError($this, 'form_field', array('poll' => $poll_app));
        }
        $poll = $polls[$value];

        // checks if form field is defined
        if (!isset($poll['form'])) {
            throw new sfValidatorError($this, 'form_field', array('poll' => $poll_app));
        }
        $form = $poll['form'];

        // checks if form class is instantiated
        if (!class_exists($form)) {
            throw new sfValidatorError($this, 'form_class', array('poll' => $poll_app, 'form' => $form));
        }

        // checks if form class is based on aPollBaseForm
        $object = new $form();
        if (!($object instanceof aPollBaseForm)) {
            throw new sfValidatorError($this, 'form_extends', array('poll' => $poll_app, 'form' => $form));
        }

        // checks if view_template has been defined and if the file exist
        if (isset($poll['view_template'])) {

            if (!$this->checkTemplate($poll, 'view_template')) {
                throw new sfValidatorError($this, 'view_template', array('partial' => $poll['view_template']));
            }
        }

        // checks if submit_action has been defined and if the action exist
        if (isset($poll['submit_action'])) {

            $list = $this->getModuleAndAction($poll['submit_action']);

            $controller = sfContext::getInstance()->getController();

            if (!$controller->actionExists($list['module'], $list['action'])) {
                throw new sfValidatorError($this, 'submit_action', array('action' => $poll['submit_action']));
            }
        }

        // checks if view_template has been defined and if the file exist
        if (isset($poll['submit_success_template'])) {

            if (!$this->checkTemplate($poll, 'submit_success_template')) {
                throw new sfValidatorError($this, 'submit_success_template', array('template' => $poll['submit_success_template']));
            }
        }


        // checks if allow_multiple_submissions is defined and if the values are right
        if (isset($poll['allow_multiple_submissions'])) {

            if (!($poll['allow_multiple_submissions'] === true) || ($poll['allow_multiple_submissions'] === false)) {
                throw new sfValidatorError($this, 'multiple_submissions');
            }
        }

        // checks if cookie_lifetime is well defined
        if (isset($poll['cookie_lifetime'])) {

            $val = new sfValidatorNumber(array('min' => 0));

            try {
                $val->clean($poll['cookie_lifetime']);
            } catch (Exception $exc) {
                throw new sfValidatorError($this, 'cookie_lifetime');
            }
        }

        // checks if the send_notification contains true or false
        if (isset($poll['send_notification'])) {

            $val = new sfValidatorBoolean();

            try {
                $val->clean($poll['send_notification']);
            } catch (Exception $exc) {
                throw new sfValidatorError($this, 'send_notification');
            }
        }


        // checks if the send_to field defins a valid email or a valid user
        if (isset($poll['send_to'])) {

            $what = aPollToolkit::isUserOrEmail($poll['send_to']);

            if (!in_array($what, array('user', 'email'))) {
                throw new sfValidatorError($this, 'send_email', array('field' => 'send_to', 'global_field' => 'to'));
            }
        }

        // checks if the send_from field defins a valid email or a valid user
        if (isset($poll['send_from'])) {

            $what = aPollToolkit::isUserOrEmail($poll['send_from']);

            if (!in_array($what, array('user', 'email'))) {
                throw new sfValidatorError($this, 'send_email', array('field' => 'send_from', 'global_field' => 'from'));
            }
        }


        // checks if email_title_partial has been defined and if the file exist
        if (isset($poll['email_title_partial'])) {

            if (!$this->checkTemplate($poll, 'email_title_partial')) {
                throw new sfValidatorError($this, 'email_title', array('template' => $poll['email_title_partial']));
            }
        }

        // checks if email_body_partial has been defined and if the file exist
        if (isset($poll['email_body_partial'])) {

            if (!$this->checkTemplate($poll, 'email_body_partial')) {
                throw new sfValidatorError($this, 'email_body', array('template' => $poll['email_body_partial']));
            }
        }


        return $value;
    }

    protected function getModuleAndAction($ref) {

        if (false !== $sep = strpos($ref, '/')) {
            $moduleName = substr($ref, 0, $sep);
            $templateName = substr($ref, $sep + 1);
        } else {
            $moduleName = 'aPollSlot';
            $templateName = $ref;
        }


        return array('module' => $moduleName, 'action' => $templateName);
    }

    protected function checkTemplate($conf, $field) {

        $templateName = $conf[$field];

        $list = $this->getModuleAndAction($templateName);

        $template = '_' . $list['action'] . '.php';

        $directory = sfContext::getInstance()->getConfiguration()->getTemplateDir($list['module'], $template);

        return is_readable($directory . '/' . $template);
    }

}

