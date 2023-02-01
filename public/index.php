<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\ArrayUserRepository;
use App\UserRepository;
use App\UserRepositoryInterface;
use App\Validator;
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;

session_start();

function getUserRepository($request): UserRepositoryInterface
{
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);

    return new UserRepository(new ArrayUserRepository($users));
}

function saveUsersToCookie($response, array $users)
{
    return $response->withHeader('Set-Cookie', 'users=' . json_encode($users) . '; Path=/');
}

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

// Получаем роутер – объект отвечающий за хранение и обработку маршрутов
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->post('/login', function ($request, $response) use ($router) {
    $nickname = $request->getParsedBodyParam('nickname');
    $repo = getUserRepository($request);

    if ($repo->findByNickname($nickname)) {
        $_SESSION['isAuthenticated'] = 1;
    } else {
        $this->get('flash')->addMessage('error', "User $nickname not found");
    }

    return $response->withRedirect($router->urlFor('users'));
});

$app->delete('/logout', function ($request, $response) use ($router) {
    $_SESSION = [];
    session_destroy();

    return $response->withRedirect($router->urlFor('users'));
});

$app->get('/users', function ($request, $response) {
    $term = $request->getQueryParam('term');
    $repo = getUserRepository($request);

    $filtered = collect($repo->all())
        ->filter(fn($user) => !$term || stripos($user['nickname'], $term) === 0)
        ->all();

    $params = [
        'term'            => $term,
        'users'           => $filtered,
        'isAuthenticated' => $_SESSION['isAuthenticated'] ?? 0,
        'flash'           => $this->get('flash')->getMessages(),
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => [
            'nickname' => '',
            'email'    => '',
        ],
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('createUser');

$app->get('/users/{id}', function ($request, $response, $args) {
    $repo = getUserRepository($request);

    if (!$user = $repo->find($args['id'])) {
        return $response->withStatus(404)->write('Page not found');
    }

    return $this->get('renderer')->render($response, 'users/show.phtml', $user);
})->setName('user');

$app->post('/users', function ($request, $response) use ($router) {
    $user = $request->getParsedBodyParam('user');
    $errors = (new Validator())->validate($user);

    if (!$errors) {
        $user['id'] = uniqid();

        $repo = getUserRepository($request);
        $repo->save($user);

        $this->get('flash')->addMessage('success', 'User was added successfully');

        $response = saveUsersToCookie($response, $repo->all());

        return $response->withRedirect($router->urlFor('users'));
    }

    $params = [
        'user'   => $user,
        'errors' => $errors,
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
});

$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($router) {
    $repo = getUserRepository($request);

    if (!$user = $repo->find($args['id'])) {
        return $response->withStatus(404)->write('Page not found');
    }

    $params = [
        'user' => $user,
    ];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');

$app->patch('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $repo = getUserRepository($request);

    if (!$user = $repo->find($id)) {
        return $response->withStatus(404)->write('Page not found');
    }

    $data = $request->getParsedBodyParam('user');
    $errors = (new App\Validator())->validate($data);

    if (!$errors) {
        $user['nickname'] = $data['nickname'];
        $user['email'] = $data['email'];

        $repo->save($user);

        $this->get('flash')->addMessage('success', 'User has been updated');

        $response = saveUsersToCookie($response, $repo->all());

        return $response->withRedirect($router->urlFor('users'));
    }

    $params = [
        'user'   => $user,
        'errors' => $errors,
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/edit.phtml', $params);
});

$app->get('/users/{id}/delete', function ($request, $response, array $args) use ($router) {
    $repo = getUserRepository($request);

    if (!$user = $repo->find($args['id'])) {
        return $response->withStatus(404)->write('Page not found');
    }

    $params = [
        'user' => $user,
    ];

    return $this->get('renderer')->render($response, 'users/delete.phtml', $params);
})->setName('deleteUser');

$app->delete('/users/{id}', function ($request, $response, array $args) use ($router) {
    $repo = getUserRepository($request);

    $repo->destroy($args['id']);

    $this->get('flash')->addMessage('success', 'User has been deleted');

    $response = saveUsersToCookie($response, $repo->all());

    return $response->withRedirect($router->urlFor('users'));
});

$app->run();
