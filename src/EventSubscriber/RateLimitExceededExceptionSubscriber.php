<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;

class RateLimitExceededExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'addHeadersIfRateLimitExceeded'];
    }

    public function addHeadersIfRateLimitExceeded(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (! $exception instanceof RateLimitExceededException) {
            return;
        }

        $response = $event->getResponse() ?? new Response(null, Response::HTTP_TOO_MANY_REQUESTS);

        $response->headers->set(
            'X-Rate-Limit-Retry-After',
            (string) ($exception->getRetryAfter()->getTimestamp() - time())
        );
        $response->headers->set('X-Rate-Limit-Limit', (string) $exception->getLimit());
        $response->headers->set('X-Rate-Limit-Remaining', (string) $exception->getRemainingTokens());
        $response->headers->set('X-Rate-Limit-Reset', (string) $exception->getRetryAfter()->getTimestamp());

        $event->setResponse($response);
    }
}
