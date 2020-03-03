<?php


namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Database\Queries\UserQuery;
use App\Web\Mail;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class RegisterController extends Controller
{

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws HttpNotFoundException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function registerForm(Request $request, Response $response): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, route('home'));
        }

        if ($this->getSetting('register_enabled', 'off') === 'off') {
            throw new HttpNotFoundException($request);
        }

        return view()->render($response, 'auth/register.twig');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     * @throws HttpNotFoundException
     * @throws \Exception
     */
    public function register(Request $request, Response $response): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, route('home'));
        }

        if ($this->getSetting('register_enabled', 'off') === 'off') {
            throw new HttpNotFoundException($request);
        }

        $validator = $this->getUserCreateValidator($request);

        if ($validator->fails()) {
            return redirect($response, route('register.show'));
        }

        $activateToken = bin2hex(random_bytes(16));

        make(UserQuery::class)->create(
            param($request, 'email'),
            param($request, 'username'),
            param($request, 'password'),
            0,
            0,
            (int) $this->getSetting('default_user_quota', -1),
            $activateToken
        );

        Mail::make()
            ->from(platform_mail(), $this->config['app_name'])
            ->to(param($request, 'email'))
            ->subject(lang('mail.activate_account', [$this->config['app_name']]))
            ->message(lang('mail.activate_text', [
                param($request, 'username'),
                $this->config['app_name'],
                $this->config['base_url'],
                route('activate', ['activateToken' => $activateToken]),
            ]))
            ->send();

        $this->session->alert(lang('register_success', [param($request, 'username')]), 'success');
        $this->logger->info('New user registered.', [array_diff_key($request->getParsedBody(), array_flip(['password']))]);

        return redirect($response, route('login.show'));
    }

    /**
     * @param  Response  $response
     * @param  string  $activateToken
     * @return Response
     */
    public function activateUser(Response $response, string $activateToken): Response
    {
        if ($this->session->get('logged', false)) {
            return redirect($response, route('home'));
        }

        $userId = $this->database->query('SELECT `id` FROM `users` WHERE `activate_token` = ? LIMIT 1', $activateToken)->fetch()->id ?? null;

        if ($userId === null) {
            $this->session->alert(lang('account_not_found'), 'warning');
            return redirect($response, route('login.show'));
        }

        $this->database->query('UPDATE `users` SET `activate_token`=?, `active`=? WHERE `id` = ?', [
            null,
            1,
            $userId,
        ]);

        $this->session->alert(lang('account_activated'), 'success');
        return redirect($response, route('login.show'));
    }
}
