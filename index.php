<?php
use Symfony\Component\HttpFoundation\Response;

require_once(__DIR__ . '/vendor/autoload.php');

$app = new Silex\Application();

//@TODO change to the false in the prod version
$app['debug'] = true;

// get connection
$config = include(__DIR__ . '/config/db.php');
extract($config);
$pdo = new PDO($dsn, $user, $password, $options);

// Routes start

// GET route "/"
$app->get('/', function() {

    $text = '<p>You can use next routes:</p>';
    $text .= '<ol>';
    $text .= '<li>"/" For home page (GET)';
    $text .= '<li>"/tasks" Get list tasks (GET)';
    $text .= '<li>"/task/{id}/tags" Get one task by ID and all tags related to this task (GET)';
    $text .= '<li>"/tags" Get list tags (GET)';
    $text .= '<li>"/tag/{id}/tasks" Get one tag by ID and all tasks related to this tag (GET)';
    $text .= '<li>"/task" Add task (POST)';
    $text .= '<li>"/tag" Add tag (POST)';
    $text .= '</ol>';

    return $text;
});

// GET route "/tasks"
$app->get('/tasks', function() use($pdo) {

    $data = $pdo->prepare("SELECT * FROM `tasks`");
    $data->execute();
    $tasks = $data->fetchAll();

    return json_encode($tasks, JSON_PRETTY_PRINT);
});

// GET route "/task/{id}/tags"
$app->get('/task/{id}/tags', function($id) use($pdo) {

    // get task
    $sql = "SELECT * FROM `tasks` WHERE `id` = :id";
    $query = $pdo->prepare($sql);
    $query->execute(array('id'=>$id));
    $task['task'] = $query->fetch();

    if(empty($task['task'])) {
        return new Response(json_encode('Task with id = ' . $id .' not found'), 404);
    }

    // get tags
    $sql = "SELECT tags.name
            FROM tag_task
            JOIN tags ON tags.id = tag_task.id_tag
            WHERE tag_task.id_task = :id";

    $query = $pdo->prepare($sql);
    $query->execute(array('id'=>$id));
    $tags['tags'] = $query->fetchAll(PDO::FETCH_COLUMN, 0);

    $result = array_merge($task, $tags);

    return json_encode($result, JSON_PRETTY_PRINT);
})->assert('id', '\d+');

// GET route "/tags"
$app->get('/tags', function() use($pdo) {

    $data = $pdo->prepare("SELECT * FROM `tags`");
    $data->execute();
    $tags = $data->fetchAll();

    return json_encode($tags, JSON_PRETTY_PRINT);
});

// GET route "/tag/{id}/tasks"
$app->get('/tag/{id}/tasks', function($id) use($pdo) {

    // get tag
    $sql = "SELECT * FROM `tags` WHERE `id` = :id";
    $query = $pdo->prepare($sql);
    $query->execute(array('id'=>$id));
    $tag['tag'] = $query->fetch();

    if(empty($tag['tag'])) {
        return new Response(json_encode('Tag with id = ' . $id .' not found'), 404);
    }

    // get tasks
    $sql = "SELECT tasks.*
        	FROM tag_task
        	JOIN tasks ON tasks.id = tag_task.id_task
        	WHERE tag_task.id_tag = :id";

    $query = $pdo->prepare($sql);
    $query->execute(array('id'=>$id));
    $tasks['tasks'] = $query->fetchAll();

    $result = array_merge($tag, $tasks);

    return json_encode($result, JSON_PRETTY_PRINT);

})->assert('id', '\d+');

// POST route "/task"
$app->post('/task', function() use($app, $pdo) {

    $data = $app['request'];

    $name        = $data->get('name');
    $description = $data->get('description');

    if(empty($name) or empty($description)) {
        return new Response(json_encode('Empty params'), 204);
    } else {

        $sql = "INSERT INTO `tasks` (`name`, `description`, `date_of_creation`) VALUES(:name, :description, NOW())";
        $prep = $pdo->prepare($sql);
        $prep->execute([
            'name'        => $name,
            'description' => $description,
        ]);

        $idTask = $pdo->lastInsertId();

        // get task
        $sql = "SELECT * FROM `tasks` WHERE id = :id";
        $query = $pdo->prepare($sql);
        $query->execute(array('id'=>$idTask));
        $newTask = $query->fetch();

        return new Response(json_encode($newTask), 201);

    }
});

// POST route "/tag"
$app->post('/tag', function() use($app, $pdo) {

    $data = $app['request'];

    $name = $data->get('name');

    if(empty($name)) {
        return new Response(json_encode('Empty params'), 204);
    } else {

        $sql = "INSERT INTO `tags` (`name`, `date_of_creation`) VALUES(:name, NOW())";
        $prep = $pdo->prepare($sql);
        $prep->execute(['name'=>$name]);

        $idTag = $pdo->lastInsertId();

        // get tag
        $sql = "SELECT * FROM `tags` WHERE id = :id";
        $query = $pdo->prepare($sql);
        $query->execute(array('id'=>$idTag));
        $newTag = $query->fetch();

        return new Response(json_encode($newTag), 201);

    }
});

$app->run();