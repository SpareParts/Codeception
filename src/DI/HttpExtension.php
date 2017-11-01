<?php

/*
 * This file is part of the Arachne
 *
 * Copyright (c) Jáchym Toušek (enumag@gmail.com)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Arachne\Codeception\DI;

use Arachne\Codeception\Http\Response;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class HttpExtension extends CompilerExtension
{
    protected $defaultConfig = [
        'overrideRequest' => true,
        'overrideResponse' => true,
        'fixDrahakRestResponse' => false,
    ];

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();

        $config = $this->getConfig($this->defaultConfig);

        if ($config['overrideRequest']) {
            $request = $builder->getByType('Nette\Http\IRequest') ?: 'httpRequest';
            if ($builder->hasDefinition($request)) {
                $builder->getDefinition($request)
                    ->setClass('Nette\Http\Request')
                    ->setFactory('Arachne\Codeception\Http\Request');
            }
        }

        if ($config['overrideResponse']) {
            $response = $builder->getByType('Nette\Http\IResponse') ?: 'httpResponse';
            if ($builder->hasDefinition($response)) {
                $builder->getDefinition($response)
                    ->setClass('Nette\Http\IResponse')
                    ->setFactory('Arachne\Codeception\Http\Response');
            }
        } else {
            $response = $builder->getByType('Nette\Http\IResponse') ?: 'httpResponse';
            if ($builder->hasDefinition($response)) {
                $def = $builder->getDefinition($response);
                $def->setClass('Nette\Http\IResponse');
            }
        }

//        if ($config['fixDrahakRestResponse']) {
//            $response = $builder->getByType('Nette\Http\IResponse') ?: 'httpResponse';
//            if ($builder->hasDefinition($response)) {
//				$def = $builder->getDefinition($response);
//                $def->setClass('Nette\Http\IResponse');
//            }
//
//            $responseFactory = $builder->getByType('Drahak\Restful\Http\ResponseFactory');
//            $builder->getDefinition($responseFactory)
//                ->addSetup('$service->setResponse(?)', [ new Statement(Response::class) ]);
//        }
    }
}
