<?php

declare(strict_types=1);

namespace App\Normalizer;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PaginatorNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    ) {
        //
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        if (! $object instanceof Paginator) {
            return new \LogicException('Not supported type');
        }

        $data = [];
        $data['data'] = [];
        $data['total'] = $object->count();

        foreach ($object->getIterator() as $item) {
            $data['data'][] = $this->normalizer->normalize($item, $format, $context);
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Paginator;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Paginator::class => true,
        ];
    }
}
