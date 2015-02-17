<?php
namespace Codeception\Module;


class NodusWebDriver extends WebDriver
{


    /**
     * @param string $username
     */
    public function amLoggedInAs($username)
    {
        $this->amOnPage('/debug-api/simple-login?username='.$username);
    }


}