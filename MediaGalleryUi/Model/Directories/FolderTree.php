<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MediaGalleryUi\Model\Directories;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;

/**
 * Build folder tree structure by path
 */
class FolderTree
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $path;

    /**
     * Constructor
     *
     * @param Filesystem $filesystem
     * @param string $path
     */
    public function __construct(
        Filesystem $filesystem,
        string $path
    ) {
        $this->filesystem = $filesystem;
        $this->path = $path;
    }

    /**
     * Return directory folder structure in array
     *
     * @param bool $skipRoot
     * @return array
     */
    public function buildTree(bool $skipRoot = true): array
    {
        return $this->buildFolderTree($this->getDirectories(), $skipRoot);
    }

    /**
     * Build directory tree array in format for jstree strandart
     *
     * @return array
     */
    private function getDirectories(): array
    {
        $directories = [];

        /** @var Read $directory */
        $directory = $this->filesystem->getDirectoryRead($this->path);

        if (!$directory->isDirectory()) {
            return $directories;
        }

        foreach ($directory->readRecursively() as $index => $path) {
            if (!$directory->isDirectory($path)) {
                continue;
            }

            $pathArray = explode('/', $path);
            $directories[] = [
                'data' => count($pathArray) > 0 ? end($pathArray) : $path,
                'attr' => ['id' => $index],
                'metadata' => [
                    'path' => $path
                ],
                'path_array' => $pathArray
            ];
        }
        return $directories;
    }

    /**
     * Build folder tree structure by provided directories path
     *
     * @param array $directories
     * @param bool $skipRoot
     * @return array
     */
    private function buildFolderTree(array $directories, bool $skipRoot): array
    {
        $tree = [
            'name' => 'root',
            'path' => '/',
            'children' => []
        ];
        foreach ($directories as $idx => &$node) {
            $node['children'] = [];
            $result = $this->findParent($node, $tree);
            $parent = & $result['treeNode'];

            $parent['children'][] =& $directories[$idx];
        }
        return $skipRoot ? $tree['children'] : $tree;
    }

    /**
     * Find parent directory
     *
     * @param array $node
     * @param array $treeNode
     * @param int $level
     * @return array
     */
    private function findParent(array &$node, array &$treeNode, int $level = 0): array
    {
        $nodePathLength = count($node['path_array']);
        $treeNodeParentLevel = $nodePathLength - 1;

        $result = ['treeNode' => &$treeNode];

        if ($nodePathLength <= 1 || $level > $treeNodeParentLevel) {
            return $result;
        }

        foreach ($treeNode['children'] as $idx => &$tnode) {
            if ($node['path_array'][$level] === $tnode['path_array'][$level]) {
                return $this->findParent($node, $tnode, $level + 1);
            }
        }
        return $result;
    }
}
