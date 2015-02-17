<?php
namespace Codeception\Module;


use DerpTest\Machinist\Machinist;
use Symfony\Component\BrowserKit\Cookie;

class NodusPhpBrowser extends PhpBrowser
{


    /**
     * Switch to main web testing
     */
    public function wantToTestWeb()
    {
        $url = $this->config['domains']['web'];
        $this->_reconfigure(['url' => $url]);
    }


    /**
     * Switch to mobile web testing
     */
    public function wantToTestMobile()
    {
        $url = $this->config['domains']['mobile'];
        $this->_reconfigure(['url' => $url]);
    }


    /**
     * Switch to admin web testing
     */
    public function wantToTestAdmin()
    {
        $url = $this->config['domains']['admin'];
        $this->_reconfigure(['url' => $url]);
    }


    /**
     * Switch to pornfile web testing
     */
    public function wantToTestPornfile()
    {
        $url = $this->config['domains']['pornfile'];
        $this->_reconfigure(['url' => $url]);
    }


    /**
     * @param string $username
     */
    public function amLoggedInAs($username)
    {
        $this->amOnPage('/debug-api/simple-login?username='.$username);
    }


}