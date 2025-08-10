<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class SearchSampleFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (!$this->isPropertyEnabled($property, $resourceClass) || !is_string($value) || empty(trim($value))) {
            return;
        }

        // Split by non-word character groups and trim each chunk
        $chunks = array_filter(
            array_map('trim', preg_split('/\W+/', $value)),
            fn ($chunk) => '' !== $chunk
        );

        $chunkCount = count($chunks);
        if (0 === $chunkCount || $chunkCount > 4) {
            return;
        }

        $this->processChunks($chunks, $queryBuilder, $queryNameGenerator);
    }

    private function processChunks(array $chunks, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $chunkCount = count($chunks);

        switch ($chunkCount) {
            case 1:
                $this->handleOneChunk($chunks[0], $queryBuilder, $queryNameGenerator);
                break;
            case 2:
                $this->handleTwoChunks($chunks[0], $chunks[1], $queryBuilder, $queryNameGenerator);
                break;
            case 3:
                $this->handleThreeChunks($chunks[0], $chunks[1], $chunks[2], $queryBuilder, $queryNameGenerator);
                break;
            case 4:
                $this->handleFourChunks($chunks[0], $chunks[1], $chunks[2], $chunks[3], $queryBuilder, $queryNameGenerator);
                break;
        }
    }

    private function handleOneChunk(string $chunk, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        if ($this->isNumeric($chunk)) {
            // 1b. numeric: filter using cast(sample_number AS STRING)
            $this->addSampleNumberFilter($chunk, $queryBuilder, $queryNameGenerator);
        } else {
            // 1a. string: filter using site code
            $this->addSiteCodeFilter($chunk, $queryBuilder, $queryNameGenerator);
        }
    }

    private function handleTwoChunks(string $chunk1, string $chunk2, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $chunk1IsNumeric = $this->isNumeric($chunk1);
        $chunk2IsNumeric = $this->isNumeric($chunk2);

        if (!$chunk1IsNumeric && !$chunk2IsNumeric) {
            // 2a. both string: filter using site code AND type code
            $this->addSiteCodeFilter($chunk1, $queryBuilder, $queryNameGenerator);
            $this->addTypeCodeFilter($chunk2, $queryBuilder, $queryNameGenerator);
        } elseif ($chunk1IsNumeric && $chunk2IsNumeric) {
            // 2b. both numeric: filter cast(sample_year AS STRING) AND cast(sample_number AS STRING)
            $this->addSampleYearFilter($chunk1, $queryBuilder, $queryNameGenerator);
            $this->addSampleNumberFilter($chunk2, $queryBuilder, $queryNameGenerator);
        } else {
            // 2c. one numeric, one string: use string for site code AND numeric for cast(sample_number AS STRING)
            if ($chunk1IsNumeric) {
                $this->addSiteCodeFilter($chunk2, $queryBuilder, $queryNameGenerator);
                $this->addSampleNumberFilter($chunk1, $queryBuilder, $queryNameGenerator);
            } else {
                $this->addSiteCodeFilter($chunk1, $queryBuilder, $queryNameGenerator);
                $this->addSampleNumberFilter($chunk2, $queryBuilder, $queryNameGenerator);
            }
        }
    }

    private function handleThreeChunks(string $chunk1, string $chunk2, string $chunk3, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $chunk1IsNumeric = $this->isNumeric($chunk1);
        $chunk2IsNumeric = $this->isNumeric($chunk2);
        $chunk3IsNumeric = $this->isNumeric($chunk3);

        $numericCount = ($chunk1IsNumeric ? 1 : 0) + ($chunk2IsNumeric ? 1 : 0) + ($chunk3IsNumeric ? 1 : 0);
        $stringCount = 3 - $numericCount;

        if (3 === $stringCount) {
            // 3a. three strings: discard the third one and use option 2a
            $this->addSiteCodeFilter($chunk1, $queryBuilder, $queryNameGenerator);
            $this->addTypeCodeFilter($chunk2, $queryBuilder, $queryNameGenerator);
        } elseif (3 === $numericCount) {
            // 3b. three numeric: discard the third one and use option 2b
            $this->addSampleYearFilter($chunk1, $queryBuilder, $queryNameGenerator);
            $this->addSampleNumberFilter($chunk2, $queryBuilder, $queryNameGenerator);
        } elseif (2 === $stringCount && 1 === $numericCount) {
            // 3c. two string, one numeric: filter using site code AND type code AND cast(sample_number AS STRING)
            // 3d. two string, one numeric: filter using site code AND cast(sample_year AS STRING) AND cast(sample_number AS STRING)

            // Find the positions of strings and numeric
            $stringChunks = [];
            $numericChunk = null;

            if (!$chunk1IsNumeric) {
                $stringChunks[] = $chunk1;
            } else {
                $numericChunk = $chunk1;
            }

            if (!$chunk2IsNumeric) {
                $stringChunks[] = $chunk2;
            } else {
                $numericChunk = $chunk2;
            }

            if (!$chunk3IsNumeric) {
                $stringChunks[] = $chunk3;
            } else {
                $numericChunk = $chunk3;
            }

            // Use first two strings for site code and type code, numeric for sample number
            $this->addSiteCodeFilter($stringChunks[0], $queryBuilder, $queryNameGenerator);
            $this->addTypeCodeFilter($stringChunks[1], $queryBuilder, $queryNameGenerator);
            $this->addSampleNumberFilter($numericChunk, $queryBuilder, $queryNameGenerator);
        }
    }

    private function handleFourChunks(string $chunk1, string $chunk2, string $chunk3, string $chunk4, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $chunks = [$chunk1, $chunk2, $chunk3, $chunk4];
        $isNumeric = array_map([$this, 'isNumeric'], $chunks);
        $numericCount = array_sum($isNumeric);
        $stringCount = 4 - $numericCount;

        if (2 === $stringCount && 2 === $numericCount) {
            // 4a. two string, two numeric: filter using site code AND type code AND cast(sample_year AS STRING) AND cast(sample_number AS STRING)
            $stringChunks = [];
            $numericChunks = [];

            for ($i = 0; $i < 4; ++$i) {
                if ($isNumeric[$i]) {
                    $numericChunks[] = $chunks[$i];
                } else {
                    $stringChunks[] = $chunks[$i];
                }
            }

            $this->addSiteCodeFilter($stringChunks[0], $queryBuilder, $queryNameGenerator);
            $this->addTypeCodeFilter($stringChunks[1], $queryBuilder, $queryNameGenerator);
            $this->addSampleYearFilter($numericChunks[0], $queryBuilder, $queryNameGenerator);
            $this->addSampleNumberFilter($numericChunks[1], $queryBuilder, $queryNameGenerator);
        } elseif (3 === $stringCount && 1 === $numericCount) {
            // 4b. three strings, one numeric: discard the third string and use option 2a AND 1b
            $stringChunks = [];
            $numericChunk = null;

            for ($i = 0; $i < 4; ++$i) {
                if ($isNumeric[$i]) {
                    $numericChunk = $chunks[$i];
                } else {
                    $stringChunks[] = $chunks[$i];
                }
            }

            // Use first two strings
            $this->addSiteCodeFilter($stringChunks[0], $queryBuilder, $queryNameGenerator);
            $this->addTypeCodeFilter($stringChunks[1], $queryBuilder, $queryNameGenerator);
            $this->addSampleNumberFilter($numericChunk, $queryBuilder, $queryNameGenerator);
        } elseif (1 === $stringCount && 3 === $numericCount) {
            // 4c. three numeric, one string: discard the third numeric and use option 2b AND 1a
            $numericChunks = [];
            $stringChunk = null;

            for ($i = 0; $i < 4; ++$i) {
                if ($isNumeric[$i]) {
                    $numericChunks[] = $chunks[$i];
                } else {
                    $stringChunk = $chunks[$i];
                }
            }

            $this->addSiteCodeFilter($stringChunk, $queryBuilder, $queryNameGenerator);
            // Use first two numeric chunks
            $this->addSampleYearFilter($numericChunks[0], $queryBuilder, $queryNameGenerator);
            $this->addSampleNumberFilter($numericChunks[1], $queryBuilder, $queryNameGenerator);
        } elseif (4 === $stringCount) {
            // 4d. four string: discard last two entries and use 2a
            $this->addSiteCodeFilter($chunk1, $queryBuilder, $queryNameGenerator);
            $this->addTypeCodeFilter($chunk2, $queryBuilder, $queryNameGenerator);
        } elseif (4 === $numericCount) {
            // 4e. four numeric: discard last two entries and use 2b
            $this->addSampleYearFilter($chunk1, $queryBuilder, $queryNameGenerator);
            $this->addSampleNumberFilter($chunk2, $queryBuilder, $queryNameGenerator);
        }
    }

    private function addSiteCodeFilter(string $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
        $siteAlias = $queryNameGenerator->generateJoinAlias('site');
        $parameterName = $queryNameGenerator->generateParameterName('site_code');

        // Join with the site table if not already joined
        $this->joinSiteIfNeeded($queryBuilder, $alias, $siteAlias);

        $queryBuilder
            ->andWhere(sprintf('%s.code LIKE :%s', $siteAlias, $parameterName))
            ->setParameter($parameterName, '%'.strtoupper($value).'%');
    }

    private function addTypeCodeFilter(string $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
        $typeAlias = $queryNameGenerator->generateJoinAlias('sampleType');
        $parameterName = $queryNameGenerator->generateParameterName('type_code');

        // Join with the sample type table if not already joined
        $this->joinSampleTypeIfNeeded($queryBuilder, $alias, $typeAlias);

        $queryBuilder
            ->andWhere(sprintf('%s.code LIKE :%s', $typeAlias, $parameterName))
            ->setParameter($parameterName, '%'.strtoupper($value).'%');
    }

    private function addSampleNumberFilter(string $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('sample_number');

        $queryBuilder
            ->andWhere(sprintf('CAST(%s.number AS STRING) LIKE :%s', $alias, $parameterName))
            ->setParameter($parameterName, $value.'%');
    }

    private function addSampleYearFilter(string $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('sample_year');

        $queryBuilder
            ->andWhere(sprintf('CAST(%s.year AS STRING) LIKE :%s', $alias, $parameterName))
            ->setParameter($parameterName, $value.'%');
    }

    private function joinSiteIfNeeded(QueryBuilder $queryBuilder, string $alias, string $siteAlias): void
    {
        // Check if site join already exists
        $joins = $queryBuilder->getDQLPart('join');
        $siteJoinExists = false;

        foreach ($joins as $joinArray) {
            foreach ($joinArray as $join) {
                if (str_contains($join->getJoin(), '.site') || $join->getAlias() === $siteAlias) {
                    $siteJoinExists = true;
                    break 2;
                }
            }
        }

        if (!$siteJoinExists) {
            $queryBuilder->leftJoin(sprintf('%s.site', $alias), $siteAlias);
        }
    }

    private function joinSampleTypeIfNeeded(QueryBuilder $queryBuilder, string $alias, string $typeAlias): void
    {
        // Check if sample type join already exists
        $joins = $queryBuilder->getDQLPart('join');
        $typeJoinExists = false;

        foreach ($joins as $joinArray) {
            foreach ($joinArray as $join) {
                if (str_contains($join->getJoin(), '.type') || $join->getAlias() === $typeAlias) {
                    $typeJoinExists = true;
                    break 2;
                }
            }
        }

        if (!$typeJoinExists) {
            $queryBuilder->leftJoin(sprintf('%s.type', $alias), $typeAlias);
        }
    }

    private function isNumeric(string $value): bool
    {
        return is_numeric($value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Smart search for samples. Supports flexible input patterns: single values (site code or sample number), two values (site+type codes, year+number, or site+number), three values (site+type+number), or four values (site+type+year+number). Use any non-word characters as separators (spaces, dots, hyphens, etc.).',
                'openapi' => [
                    'example' => 'ME.GE.34.93',
                    'allowEmptyValue' => true,
                    'allowReserved' => false,
                    'style' => 'simple',
                    'explode' => false,
                ],
            ],
        ];
    }
}
