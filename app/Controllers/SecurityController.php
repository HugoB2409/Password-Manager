<?php namespace Controllers;

use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\Response;
use Zephyrus\Security\Authorization;
use Zephyrus\Security\ContentSecurityPolicy;
use Zephyrus\Security\Controller as ZephyrusBaseController;
use Zephyrus\Security\CrossOriginResourcePolicy;

/**
 * This controller class acts as a security middleware for the application. All controllers should inherit this
 * middleware to ensure proper security and maintainability. This class should be used to specify authorizations, CSP
 * headers, intrusion detection behaviors, and any another security specific settings for your application. The Zephyrus
 * base security controller which this class extends from contains basic security behaviors that all applications should
 * have (CSRF, security headers and authorization engine).
 *
 * Class SecurityController
 * @package Controllers
 */
abstract class SecurityController extends ZephyrusBaseController
{
    /**
     * This method is called before every route call from inherited controllers and makes sure to check if there is an
     * intrusion detection, or an authorization problem and sets the Content Security Policy headers. Any other security
     * considerations that should be checked BEFORE processing any route should be done here.
     *
     * Parent call should ensure check for basic security measures in any application such as CSRF validation, intrusion
     * detection and authorization access.
     *
     * @return Response|null
     */
    public function before(): ?Response
    {
        $this->applyContentSecurityPolicies();
        $this->setupAuthorizations();

        /**
         * Uncomment to sent basic CORS header (Access-Control-Allow-Origin: *) to allow any domains for cross origin
         * resource sharing. Edit method for more refined properties using the CrossOriginResourcePolicy class.
         */
        $this->applyCrossOriginResourceSharing();

        /**
         * May throw an UnauthorizedAccessException, InvalidCsrfException or IntrusionDetectionException. Exception can
         * be thrown to be caught by children controllers if needed or the error handler. It is recommended to catch
         * them here to ensure a proper uniform handling of the basic security controls.
         */
        try {
            parent::before();
        } catch (IntrusionDetectionException $exception) {
            /**
             * Defines what to do when an attack attempt (mainly XSS and SQL injection) is detected in the application.
             * The impact value represents the severity of the attempt. IntrusionDetection class is a wrapper of the
             * expose library. Be careful about the action chosen to handle such case as there may have false positive
             * for legitimate clients. That is why there are no default action.
             *
             * @see https://github.com/enygma/expose
             */
            $data = $exception->getIntrusionData();
            if ($data['impact'] >= 10) {
                // Do something (logs, database report, redirect, ...)
                // return $this->abortForbidden();
            }
        } catch (InvalidCsrfException $exception) {
            /**
             * Defines what to do when the CSRF token mismatch. Meaning there's an attempt to access a route with no
             * token or with an expired already use token. By default, treat this as a forbidden access to the route.
             * This will break the middleware chain and immediately return the 403 HTTP code and thus ensure protection
             * of the route processing.
             */
            // Do something (logs, database report, redirect, ...)
            return $this->abortForbidden();
        } catch (UnauthorizedAccessException $exception) {
            /**
             * Defines what to do when the route doesn't meet the authorization requirements. By default, treat this as
             * a forbidden access to the route. This will break the middleware chain and immediately return the 403 HTTP
             * code and thus ensure protection of the route processing.
             */
            // Do something (logs, database report, redirect, ...)
            die("UNAUTHORIZED ACCESS DETECTED GO TO /login");
        }

        // No security issue found, continue processing of middleware chain or
        // route processing.
        return null;
    }

    /**
     * Defines the application authorizations for all inherited controllers. For a cleaner and maintainable codebase for
     * large projects with a huge quantities or routes with more or less complex authorization, it should be split
     * across multiple middlewares (overriding the before() method) for specific controller to set the related
     * authorizations.
     */
    private function setupAuthorizations()
    {
        parent::getAuthorization()->setMode(Authorization::MODE_WHITELIST);

        parent::getAuthorization()->addSessionRule('connected', 'userId');
        parent::getAuthorization()->addRule('public', function () {
            return true;
        });

        parent::getAuthorization()->protect('/', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/heroes', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/newPassword', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/deletePassword/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/updatePassword/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/password/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/sharePassword/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/newCreditCard', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/creditCard/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/updateCreditCard/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/deleteCreditCard/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/addFavorite/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/removeFavorite/{id}', Authorization::ALL, 'connected');
        parent::getAuthorization()->protect('/getWebsite', Authorization::ALL, 'public');
        parent::getAuthorization()->protect('/authenticate', Authorization::ALL, 'public');


        parent::getAuthorization()->protect('/logout', Authorization::ALL, 'public');
        parent::getAuthorization()->protect('/login', Authorization::ALL, 'public');
        parent::getAuthorization()->protect('/signup', Authorization::ALL, 'public');
    }

    /**
     * Defines the Content Security Policies (CSP) to use for all inherited controllers. The ContentSecurityPolicy class
     * helps to craft and maintain the CSP headers easily. These headers should be seriously crafted since they greatly
     * help to prevent cross-site scripting attacks.
     *
     * @see https://content-security-policy.com/
     */
    private function applyContentSecurityPolicies()
    {
        $csp = new ContentSecurityPolicy();
        $csp->setDefaultSources(["'self'"]);
        $csp->setFontSources(["'self'", 'https://fonts.googleapis.com', 'https://fonts.gstatic.com']);
        $csp->setStyleSources(["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com']);
        $csp->setScriptSources(["'self'", 'https://ajax.googleapis.com', 'https://maps.googleapis.com',
            'https://www.google-analytics.com', 'http://connect.facebook.net']);
        $csp->setChildSources(["'self'", 'http://staticxx.facebook.com']);
        $csp->setImageSources(["'self'", 'data:']);
        $csp->setBaseUri([$this->request->getBaseUrl()]);

        /**
         * The SecureHeader class is the instance that will actually sent all the headers concerning security including
         * the CSP. Other headers includes policy concerning iframe integration, strict transport security and xss
         * protection. These headers are sent automatically from the Zephyrus security controller this class inherits
         * from.
         */
        parent::getSecureHeader()->setContentSecurityPolicy($csp);
    }

    /**
     * Defines the Access-Control-Allow-* headers to use for all inherited controllers. The CrossOriginResourcePolicy
     * class helps craft and maintain the CORS headers easily.
     */
    private function applyCrossOriginResourceSharing()
    {
        $cors = new CrossOriginResourcePolicy();
        $cors->setAccessControlAllowOrigin('*');
        parent::getSecureHeader()->setCrossOriginResourcePolicy($cors);
    }
}
