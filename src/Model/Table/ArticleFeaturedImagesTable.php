<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cms\Model\Table;

use Burzum\FileStorage\Model\Table\FileStorageTable;
use Cake\Datasource\EntityInterface;

class ArticleFeaturedImagesTable extends FileStorageTable
{
    /**
     * Save the entity to the file storage table.
     * Please note this is a child of the ImageStorageTable.
     *
     * @see ImageStorageTable class
     * @param string $articleId the id of the article
     * @param \Cake\Datasource\EntityInterface $entity  Entity object
     *
     * @return \Cake\Datasource\EntityInterface|bool Flag whether the record has got stored or not
     */
    public function uploadImage(string $articleId, EntityInterface $entity)
    {
        $entity = $this->patchEntity($entity, [
            'adapter' => 'Local',
            'model' => 'ArticleFeaturedImage',
            'foreign_key' => $articleId,
        ]);

        return $this->save($entity);
    }
}
