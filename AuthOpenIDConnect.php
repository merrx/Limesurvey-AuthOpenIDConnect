<?php
require_once(__DIR__."/vendor/autoload.php");

use Jumbojett\OpenIDConnectClient;

class AuthOpenIDConnect extends AuthPluginBase {
    protected $storage = 'DbStorage';
    protected $settings = [
        'info' => [
            'type' => 'info',
            'content' => '<h1>OpenID Connect</h1><p>Please provide the following settings.</br>If necessary settings are missing, the default authdb login will be shown.</p>'
        ],
        'providerURL' => [
            'type' => 'string',
            'label' => 'Provider URL',
            'help' => 'Required',
            'default' => ''
        ],
        'clientID' => [
            'type' => 'string',
            'label' => 'Client ID',
            'help' => 'Required',
            'default' => ''
        ],
        'clientSecret' => [
            'type' => 'string',
            'label' => 'Client Secret',
            'help' => 'Required',
            'default' => ''
        ],
        'roleMapping' => [
            'type' => 'text',
            'label' => 'User Role Mapping',
            'help' => 'User role mapping. Each line is a mapping separated by a =. Left the the OIDC group name, right limesurvey role name.'
        ],
        'redirectURL' => [
            'type' => 'string',
            'label' => 'Redirect URL',
            'help' => 'The Redirect URL is automatically set on plugin activation.',
            'default' => '',
            'htmlOptions' => [
                'readOnly' => true,
            ]
        ]
    ];
    static protected $description = 'OpenID Connect Authenticaton Plugin for LimeSurvey.';
    static protected $name = 'AuthOpenIDConnect';

    public function init(){
        $this->subscribe('beforeActivate');
        $this->subscribe('beforeLogin');
        $this->subscribe('newUserSession');
        $this->subscribe('afterLogout');
    }

    public function beforeActivate(){
        $redirectURL = \Yii::app()->getController()->createAbsoluteUrl("admin/authentication/sa/login");
        $this->set('redirectURL', $redirectURL);
    }

    public function beforeLogin(){
        $providerURL = $this->get('providerURL', null, null, false);
        $clientID = $this->get('clientID', null, null, false);
        $clientSecret = $this->get('clientSecret', null, null, false);
        $redirectURL = $this->get('redirectURL', null, null, false);

        if(!$providerURL || !$clientSecret || !$clientID || !$redirectURL){
            // Display authdb login if necessary plugin settings are missing.
            return;
        }

        $oidc = new OpenIDConnectClient($providerURL, $clientID, $clientSecret);
        $oidc->setRedirectURL($redirectURL);
        $oidc->addScope(array('openid', 'profile', 'email'));

        if(isset($_REQUEST['error'])){
            return;
        }
        try {
            if($oidc->authenticate()){
                $username = $oidc->requestUserInfo('preferred_username');

                $user = $this->api->getUserByName($username);
                if(empty($user)){
                    $user = new User;
                    $user->users_name = $username;
                    $user->setPassword(createPassword());
                    $user->parent_id = 1;
                    $user->lang = $this->api->getConfigKey('defaultlang', 'en');

                    if(!$user->save()){
                        // Couldn't create user, navigate to authdb login.
                        return;
                    }
                    // User successfully created.
                }
                $this->updateUser($user, $oidc);

                $this->setUsername($user->users_name);
                $this->setAuthPlugin();
                return;
            }
        } catch (\Throwable $error) {
            var_dump($error); die();
            // Error occurred during authentication process, redirect to authdb login.
            return;
        }
    }

    public function newUserSession(){
        $identity = $this->getEvent()->get('identity');
        if ($identity->plugin != 'AuthOpenIDConnect') {
            return;
        }

        $user = $this->api->getUserByName($this->getUsername());

        // Shouldn't happen, but just to be sure.
        if(empty($user)){
            $this->setAuthFailure(self::ERROR_UNKNOWN_IDENTITY, gT('User not found.'));
        } else {
            $this->setAuthSuccess($user);
        }
    }

    public function afterLogout(){
        Yii::app()->getRequest()->redirect('/', true, 302);
    }

    protected function updateUser(User $user, OpenIDConnectClient $oidc) {
        $email = $oidc->requestUserInfo('email');
        $givenName = $oidc->requestUserInfo('given_name');
        $familyName = $oidc->requestUserInfo('family_name');
        $groups = $oidc->requestUserInfo('groups');

        $user->full_name = trim(implode(' ', [$givenName, $familyName]));
        $user->email = $email;
        $this->setUserRoles($user, $groups);
        $user->save();
    }

    protected function setUserRoles(User $user, array $groups) {
        UserInPermissionrole::model()->deleteAll(
            "uid = :uid",
            [":uid" => $user->uid]
        );
        $roleMapping = $this->get('roleMapping');
        $roleMapping = explode("\n", $roleMapping);
        foreach ($roleMapping as $mapping) {
            if (str_contains($mapping, '=')) {
                [$group, $roleName] = array_map('trim', explode('=', $mapping));
                if (in_array($group, $groups)) {
                    $this->addUserToRole($user, $roleName);
                }
            }
        }
    }

    protected function addUserToRole(User $user, string $roleName) {
        $roles = Permissiontemplates::model()->findAllByAttributes(['name' => $roleName]);
        if (safecount($roles) == 0) {
            return;
        }
        foreach ($roles as $role) {
            if (UserInPermissionrole::model()->exists('ptid = :ptid AND uid = :uid', [':ptid' => $role->ptid, ':uid' => $user->uid])) {
                continue;
            }
            $oModel = new UserInPermissionrole;
            $oModel->ptid = $role->ptid;
            $oModel->uid = $user->uid;
            $oModel->save();
        }
    }
}
?>
