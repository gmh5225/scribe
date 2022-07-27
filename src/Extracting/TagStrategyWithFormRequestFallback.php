<?php

namespace Knuckles\Scribe\Extracting;

use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;
use Dingo\Api\Http\FormRequest as DingoFormRequest;
use Illuminate\Routing\Route;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use Mpociot\Reflection\DocBlock;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionUnionType;

class TagStrategyWithFormRequestFallback extends Strategy
{
    use FindsFormRequestForMethod;

    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        $this->endpointData = $endpointData;
        return $this->getParametersFromDocBlockInFormRequestOrMethod($endpointData->route, $endpointData->method);
    }

    public function getParametersFromDocBlockInFormRequestOrMethod(Route $route, ReflectionFunctionAbstract $method): array
    {
        $classTags = RouteDocBlocker::getDocBlocksFromRoute($route)['class']?->getTags() ?: [];
        // If there's a FormRequest, we check there for tags.
        if ($formRequestClass = $this->getFormRequestReflectionClass($method)) {
            $formRequestDocBlock = new DocBlock($formRequestClass->getDocComment());
            $parametersFromFormRequest = $this->getFromTags($formRequestDocBlock->getTags(), $classTags);

            if (count($parametersFromFormRequest)) {
                return $parametersFromFormRequest;
            }
        }

        $methodDocBlock = RouteDocBlocker::getDocBlocksFromRoute($route)['method'];
        return $this->getFromTags($methodDocBlock->getTags(), $classTags);
    }
}