<?php

namespace App\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

readonly class CsvFormatContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private SerializerContextBuilderInterface $decorated,
    ) {
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        if (!$normalization || !isset($context['groups'])) {
            return $context;
        }

        $format = $request->getRequestFormat();

        // Apply generic transformation for CSV format
        if ('csv' === $format) {
            $context['groups'] = $this->transformGroupsForCsv($context['groups']);
        }

        return $context;
    }

    private function transformGroupsForCsv(array $groups): array
    {
        return array_map(function (string $group): string {
            // Replace any group that ends with ':acl:read' with ':export'
            if (str_ends_with($group, ':acl:read')) {
                return str_replace(':acl:read', ':export', $group);
            }

            return $group;
        }, $groups);
    }
}
