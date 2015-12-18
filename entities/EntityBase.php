<?php
namespace app\entities;

use app\transformers\Transformer;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use Yii;

class EntityBase extends ActiveRecord
{
    public function getHashids()
    {
        $pk_names = static::primaryKey();

        $pk_vals = [];
        foreach ($pk_names as $pk_name) {
            $val = $this->$pk_name;
            if (!$val)
                return null;

            $pk_vals[] = $val;
        }

        return Yii::$app->hashids->encode($pk_vals);
    }

    /**
     * @inheritdoc
     * @return EntityQuery the newly created [[EntityQuery]] instance.
     */
    public static function find()
    {
        return Yii::createObject(EntityQuery::className(), [get_called_class()]);
    }

    public function __construct($config = [])
    {
        $this->_transformers = static::createTransformers();

        parent::__construct($config);
    }

    /**
     * Delete entity and, if necessary, directories records listed in $relations. see [[linkDirectories]]
     * @param array $relations Names of directory relations, caring about
     * @throws \Exception
     */
    public function directoriesCaringDelete($relations)
    {
        $dirs = [];

        foreach ($relations as $relName) {
            $rel = $this->getRelation($relName);
            $dirs[] = [
                $rel->modelClass, // dir class
                $this->{$rel->link['id']} //dir id
            ];
        }

        parent::delete();

        foreach($dirs as $dirsItem) {
            list($class, $id) = $dirsItem;

            if ($id === null)
                continue;

            /** @var $class EntityBase */
            $dir = $class::find()
                ->select(['id', 'obschij'])
                ->where(['id' => $id])
                ->one();

            if (!$dir->obschij)
                $dir->delete();
        }
    }

    /**
     * _Легенда_:
     *   Сущность - запись в таблице, которая ссылается на запись в таблице справочника.
     *   Запись - запись в таблице справочника.
     *   Состояние ссылки у сущности \ Ввод пользователя | null             | общ ID        | строка
     *   ----------------------------------------------- | ---------------- | ------------- | -------------
     *    null                                           | ничего не делать | <ol><li>Связать сущность с записью</li></ol> | <ol><li>Создать не общ. запись в справочнике</li> <li>Связать сущность с только что созданной не общ. записью</li></ol>
     *    общ ID                                         | <ol><li>Занулить связь сущности с записью</li></ol> | <ol><li>Связать сущность с записью</li></ol> | <ol><li>Создать не общ. запись в справочнике</li> <li>Связать сущность с только что созданной не общ. записью</li></ol>
     *    не общ ID                                      | <ol><li>Занулить связь сущности с записью</li> <li>Удалить запись справочника</li></ol> | <ol><li>Связать сущность с записью</li><li>Удалить запись справочника</li></ol> | <ol><li>Изменить запись</li></ol>
     *
     * @param array $links
     *  [
     *      'relation1' => null, // clear input
     *      'relation2' => ['id' => 1627], // select from directory
     *      'relation3' => ['nazvanie' => 'tratata'] //arbitrary input
     *      'relation4' => [  // arbitrary input
     *          'nazvanie' => 'tratata',
     *          'formalnoe_nazvanie' => 'Tra ta ta'
     *      ]
     *  ]
     * @return boolean
     */
    public function linkDirectories($links)
    {
        foreach ($links as $rel => $value) {
            if (!$this->linkDirectoryInternal($rel, $value))
                return false;
        }

        return $this->save(false);
    }

    private function linkDirectoryInternal($relationName, $newDirConfig)
    {
        $rel = $this->getRelation($relationName);

        $relCol = $rel->link['id'];

        /** @var $dirClass EntityBase */
        $dirClass = $rel->modelClass;

        if ($newDirConfig !== null)
            $newDirConfig['class'] = $dirClass;

        if ($this->getIsNewRecord() || $this->$relCol === null)
            return $this->hasNotDirectoryOrCommonLinkOther($relCol, $newDirConfig);

        /** @var $oldDir EntityBase */
        $oldDir = $dirClass::findOne($this->$relCol);

        if ($oldDir->obschij === true)
            return $this->hasNotDirectoryOrCommonLinkOther($relCol, $newDirConfig);

        if ($oldDir->obschij === false)
            return $this->hasPrivateDirectoryLinkOther($relCol, $newDirConfig, $oldDir);

        throw new InvalidConfigException('Column "obschij" must be only true or false');
    }

    /**
     * Link directory for case when old directory is not present or common.
     * @param string $relCol column name referenced to directory
     * @param array|null $newDirConfig
     * @return bool
     * @throws InvalidParamException
     */
    private function hasNotDirectoryOrCommonLinkOther($relCol, $newDirConfig)
    {
        if ($newDirConfig === null || isset($newDirConfig['id']) && $newDirConfig['id'] !== null) {

            $this->$relCol = $newDirConfig === null ? null : $newDirConfig['id'];

            if (defined('YII_DEBUG'))
                $this->checkNewDirId($newDirConfig);

            return true;
        }

        $dir = Yii::createObject($newDirConfig);
        $dir->obschij = false;

        if (!$dir->save(false))
            return false;

        $this->$relCol = $dir->id;
        return true;
    }


    /**
     * Link directory for case when old directory is not common.
     * @param string $relCol column name referenced to directory
     * @param array|null $newDirConfig
     * @param EntityBase $oldDir Private dir that currently linked with entity
     * @return bool
     * @throws InvalidParamException
     */
    private function hasPrivateDirectoryLinkOther($relCol, $newDirConfig, $oldDir)
    {
        if ($newDirConfig === null || isset($newDirConfig['id']) && $newDirConfig['id'] !== null) {

            $this->$relCol = $newDirConfig === null ? null : $newDirConfig['id'];

            if (defined('YII_DEBUG'))
                $this->checkNewDirId($newDirConfig);

            if ($oldDir->delete() === false)
                return false;

            return true;
        }

        unset($newDirConfig['id']);
        Yii::configure($oldDir, $newDirConfig);

        return $oldDir->save(false);
    }

    /**
     * Throw exception if directory that will be linked in is not common.
     * @param array $config Config of directory that will be linked in
     * @throws InvalidParamException
     */
    private function checkNewDirId($config)
    {
        if (isset($newDirConfig['id']) && $newDirConfig['id'] !== null) {
            /** @var $class EntityBase */
            $class = $config['class'];

            $dir = $class::findOne($newDirConfig['id']);
            if ($dir->obschij === false)
                throw new InvalidParamException('New directory cannot be private.');
        }
    }


    /**
     * @return array
     * Item can be:
     *  - ['transName' => 'column_name', 'class_name', [params]]
     *  - ['transName' => 'column_name', $inline_transformer_config]
     *  - Transformer
     */
    public function transformations()
    {
        return [];
    }

    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            if (isset($this->_transformers[$name]))
                return $this->_transformers[$name]->transformFrom();

            return parent::__get(static::prop2col($name));
        }
    }

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            if (isset($this->_transformers[$name]))
                $this->_transformers[$name]->transformTo($value);
            else
                parent::__set(static::prop2col($name), $value);
        }
    }

    public function __isset($name)
    {
        if (parent::__isset($name))
            return true;

        if (isset($this->_transformers[$name]))
            return true;

        if (parent::__isset(static::prop2col($name)))
            return true;

        return false;
    }

    public function __unset($name)
    {
        try {
            parent::__unset($name);
        } catch (InvalidCallException $e) {
            if (isset($this->_transformers[$name]))
                unset($this->_transformers[$name]);
            else
                parent::__unset(static::prop2col($name)); //todo: May be change this behaviour?
        }
    }

    protected static function prop2col($prop)
    {
        return Inflector::camel2id($prop, '_');
    }

    /**
     * @return Transformer[]
     * @throws InvalidConfigException
     */
    public function createTransformers()
    {
        $transformers = [];

        foreach ($this->transformations() as $transformation) {
            if ($transformation instanceof Transformer) {
                $transformers[$transformation->property] = $transformation;
            } elseif (is_array($transformation)) {
                $transformer = self::createTransformer($transformation);
                $transformers[$transformer->property] = $transformer;
            } else {
                throw new InvalidConfigException('Data transformations is invalid. It must be array or Transformer object.');
            }
        }

        return $transformers;
    }

    /**
     * @param $transformation
     * @return Transformer
     * @throws InvalidConfigException
     * @see transformations()
     */
    private function createTransformer($transformation)
    {
        $config = [];

        reset($transformation);

        list($config['property'], $config['column']) = each($transformation);
        if (!is_string($config['property']) || !is_string($config['column']))
            throw new InvalidConfigException('Data transformations is invalid. First array item must be "prop" => "col" pair');

        list(,$val2) = each($transformation);

        if (is_string($val2)) {
            $config['class'] = $val2;

            list(,$params) = each($transformation);
            if (is_array($params))
                $config = ArrayHelper::merge($config, $params);
            elseif ($params !== null)
                throw new InvalidConfigException('Data transformations is invalid. If params is presented it must be array');

        } elseif (is_array($val2)) {
            $config['class'] = InlineTransformer::className();

            $config = ArrayHelper::merge($config, $val2);
        }

        $config['model'] = $this;

        return \Yii::createObject($config);
    }

    /**
     * @var Transformer[]
     */
    private $_transformers;
}