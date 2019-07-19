<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

// 配置
$config = @include 'config.php';

// 项目
$projects = @include 'projects.php';

// 创建请求对象
$request = Request::createFromGlobals();

// 项目
$project = $request->query->get('p');
// 项目配置信息
$project = isset($projects[$project]) ? $projects[$project] : [];
// WebHook 请求数据
$content = json_decode($request->getContent(), true);
// 返回信息
$message = '失败';

/**
 * 1. 项目是否存在
 * 2. 新建 Webhook 校验过程
 * 3. 推送 push 事件处理过程
 */
if(empty($project)) {   // 项目不存在
    $message = '项目不存在';
} else if(isset($content['zen'])) {     // 新建 Webhook 校验过程
    // 钩子
    $hook = $content['hook'];
    // 分支
    $repo = $content['repository']; 

    if($hook['config']['secret'] != $project['secret']) {   // 判断密码是否匹配
        $message = '密码不匹配';
    } else if ($repo['full_name'] != $project['name']) {  // 判断远程仓库是否匹配
        $message = '远程仓库不匹配';
    } else {
        $message = '校验成功';
    }
} else {      // 事件处理过程
    // 分支
    $ref = $config[$project['type']]['ref'] . $project['branch'];
    // 判断事件分支是否匹配
    if($content['ref'] != $ref) {
        $message = '分支不匹配';
    } else {    // 事件处理过程
        // 命令 
        $cmd = '';
        $git = $config['git'];
        $url = sprintf($config[$project['type']]['url'], $project['name']);
        $path = $project['path'];
        $branch = $project['branch'];
         
        // 判断本地项目是否存在
        $exist = is_dir($path . '/.git') && is_file($path . '/.git/HEAD');
        if($exist) {    // 本地项目存在: 进入项目目录, 拉取代码, 检出指定分支
            $cmd = 'cd ' . $path . ' && ' . $git . ' pull && ' . $git . ' checkout ' . $branch;
        } else {        // 本地项目不存在: 克隆项目, 进入项目目录, 检出指定分支
            $url = sprintf($config[$project['type']]['url'], $project['name']);
            $cmd = $git . ' clone ' . $url . ' ' . $path . ' && cd ' . $path . ' && ' . $git . ' checkout ' . $branch;
        }

        // 执行命令
        $process = new Process($cmd);
        try {
            $process->mustRun();
            $message = $process->getOutput();

            // 执行 Composer
            if($project['composer'] != '') {
                $composer = 'cd ' . $path . ' && '  . $config['php'] . $config['composer'] . ' ' . $project['composer'];
                $process = new Process($composer);
                $process->run();
            }

        } catch (ProcessFailedException $e) {
            $message = $e->getMessage();
        }
    }
}


// 创建响应对象
$response = new JsonResponse();
// 返回信息
$response->setData([
    'status' => 0,
    'message' => $message,
    'project' => $project
]);

// 返回
$response->send();