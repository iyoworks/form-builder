<?php namespace Flynsarmy\FormBuilder;

use Closure;
use Flynsarmy\FormBuilder\Exceptions\FieldAlreadyExists;
use Flynsarmy\FormBuilder\Exceptions\FieldNotFound;
use Flynsarmy\FormBuilder\Exceptions\UnknownType;
use Flynsarmy\FormBuilder\Helpers\ArrayHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class Form
 * @property \Illuminate\Database\Eloquent\Model|\stdClass $model
 * @property string $actionType
 * @property string|array $action
 * @property array $fieldNames
 * @property string $rendererName
 */
class Form extends Element
{
    use Traits\Bindable;

    /**
     * @var FormBuilderManager
     */
    private $manager;
    /**
     * @var FormRenderer
     */
    protected $_renderer;
    /**
     * @var array|Field[]
     */
    protected $fields = [], $buffers = [];
    /**
     * @var array|Element[]
     */
    protected $rows = [];

    protected $properties = array(
        'autoLabels' => true,
        'model' => null,
        'action' => [],
        'actionType' => 'url',
        'fieldNames' => [],
        'rendererName' => null,
    );

    /**
     * @param FormBuilderManager $manager
     * @param string $rendererName
     * @param array $attributes
     * @param array $properties
     */
    public function __construct(FormBuilderManager $manager, $rendererName, array $attributes = [], array $properties = [])
    {
        parent::__construct($attributes, $properties);
        $this->manager = $manager;
        $this->rendererName = $rendererName;
    }

    /**
     * Add a addRow of field to the form
     * @param \Closure $closure     the form is passed into the closure,
     *                              any fields created in the closure will be added to the addRow
     * @param array|string $rowId   defaults to a random string
     * @return Element              the element object of the addRow
     */
    public function addRow(\Closure $closure, $rowId = null)
    {
        if (is_null($rowId)) $rowId = 'row-'.Str::random(8);
        $this->rows[$rowId] = new Element(['id' => $rowId]);
        $this->rows[$rowId]->addClass('field-row');
        $this->buffer(['row' => $rowId], $closure);
        return $this->rows[$rowId];
    }

    /**
     * @param array $properties
     * @param callable $callable
     * @return $this
     */
    public function buffer(array $properties, callable $callable)
    {
        $bufferId = count($this->buffers)+1;
        $this->buffers[$bufferId] = [];
        call_user_func($callable, $this);
        $fields = array_pull($this->buffers, $bufferId, []);
        foreach ($fields as $field) {
            $field->setProperties($properties);
        }
        return $this;
    }

    /**
     * Add a name to prepend to every field's name.
     * EX: <input name='$name[some_field]'>, <input name='$name[another_name][some_field]'>
     * @param string|dynamic $name
     * @return $this
     */
    public function addFieldName($name)
    {
        $args = func_get_args();
        $args = array_reverse($args);
        $this->fieldNames = array_merge($this->fieldNames, $args);
        return $this;
    }

    /**
     * Set the form's action attribute to resolve to a named route
     * @param $action
     * @return $this
     */
    public function route($action)
    {
        return $this->url($action, 'route');
    }

    /**
     * Set the form's action attribute to resolve to a controller action
     * @param $action
     * @return $this
     */
    public function action($action)
    {
        return $this->url($action, 'action');
    }

    /**
     * Set the form's action attribute
     * @param $action
     * @param string $actionType examples: url,action,route
     * @return $this
     */
    public function url($action, $actionType = 'url')
    {
        $this->action = $action;
        $this->actionType = $actionType;
        return $this;
    }

    /**
     * Add a callback that triggers before the form is rendered
     * @param callable $callback
     * @return $this
     */
    public function beforeForm(\Closure $callback)
    {
        $this->bind('beforeForm', $callback);
        return $this;
    }

    /**
     * Add a callback that triggers after the form is rendered
     * @param callable $callback
     * @return $this
     */
    public function afterForm(\Closure $callback)
    {
        $this->bind('afterForm', $callback);
        return $this;
    }

    /**
     * Add a callback that triggers before every field is rendered
     * @param callable $callback
     * @return $this
     */
    public function beforeField(\Closure $callback)
    {
        $this->bind('beforeField', $callback);
        return $this;
    }

    /**
     * Add a callback that triggers after every field is rendered
     * @param callable $callback
     * @return $this
     */
    public function afterField(\Closure $callback)
    {
        $this->bind('afterField', $callback);
        return $this;
    }

    /**
     * Add a new field to the form
     *
     * @param  string $slug Unique identifier for this field
     * @param  string $type Type of field
     *
     * @throws Exceptions\FieldAlreadyExists
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function add($slug, $type = null)
    {
        if ( isset($this->fields[$slug]) )
            throw new FieldAlreadyExists("Field with slug '$slug' has already been added to this form.");

        return $this->addAtPosition(sizeof($this->fields), $slug, $type);
    }

    /**
     * Add a new field to the form
     *
     * @param  string $existingId slug of field to insert before
     * @param  string $slug Unique identifier for this field
     * @param  string $type Type of field
     *
     * @throws Exceptions\FieldNotFound
     * @throws Exceptions\FieldAlreadyExists
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function addBefore($existingId, $slug, $type = null)
    {
        $keyPosition = ArrayHelper::getKeyPosition($this->fields, $existingId);
        if ( $keyPosition == -1 )
            throw new FieldNotFound("Field with slug '$existingId' does't exist.");

        if ( isset($this->fields[$slug]) )
            throw new FieldAlreadyExists("Field with slug '$slug' has already been added to this form.");

        return $this->addAtPosition($keyPosition, $slug, $type);
    }

    /**
     * Add a new field to the form
     *
     * @param  string $existingId slug of field to insert after
     * @param  string $slug Unique identifier for this field
     * @param  string $type Type of field
     *
     * @throws Exceptions\FieldNotFound
     * @throws Exceptions\FieldAlreadyExists
     * @return \Flynsarmy\FormBuilder\Field
     *
     */
    public function addAfter($existingId, $slug, $type = null)
    {
        $keyPosition = ArrayHelper::getKeyPosition($this->fields, $existingId);
        if ( $keyPosition == -1 )
            throw new FieldNotFound("Field with slug '$existingId' does't exist.");

        if ( isset($this->fields[$slug]) )
            throw new FieldAlreadyExists("Field with slug '$slug' has already been added to this form.");

        return $this->addAtPosition(++$keyPosition, $slug, $type);
    }

    /**
     * Add a new field to the form at a given position
     * binders: newField, new{Fieldtype}Field
     *
     * @param  integer $position Array index position to add the field
     * @param  string $slug Unique identifier for this field
     * @param  string $type Type of field, defaults to 'text'
     *
     * @throws Exceptions\UnknownType
     * @return \Flynsarmy\FormBuilder\Field
     */
    protected function addAtPosition($position, $slug, $type = null)
    {
        if (is_null($type)) $type = 'text';
        if (!$this->isValidType($type))
            throw new UnknownType($type);
        $field = new Field($this, $slug, $type);
        $field->row = 'row-'.count($this->fields)*rand(1, 10).count($this->fields);
        $this->fire('newField', $field);
        $this->fire('new'.Str::studly($field->type).'Field', $field);
        if (!empty($this->buffers))
        {
            foreach($this->buffers as $k => $buffer)
            {
                $buffer[] = $field;
                $this->buffers[$k] = $buffer;
            }
        }
        $this->fields = ArrayHelper::insert($this->fields, [$field->slug => $field], $position);
        return $field;
    }

    /**
     * Retrieve a field with given slug
     *
     * @param  string $slug Unique identifier for the field
     *
     * @throws Exceptions\FieldNotFound
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function getField($slug)
    {
        if ( ! $this->hasField($slug) )
            throw new FieldNotFound("Field with slug '$slug' does't exist.");

        return $this->fields[$slug];
    }

    /**
     * Determine if a field exists
     *
     * @param  string $slug Unique identifier for the field
     * @return bool
     */
    public function hasField($slug)
    {
        return isset($this->fields[$slug]);
    }

    /**
     * Remove a field from the form by slug
     *
     * @param  string $slug Unique identifier for the field
     *
     * @throws Exceptions\FieldNotFound
     * @return \Flynsarmy\FormBuilder\Form
     */
    public function remove($slug)
    {
        if ( !isset($this->fields[$slug]) )
            throw new FieldNotFound("Field with slug '$slug' does't exist.");

        unset($this->fields[$slug]);

        return $this;
    }

    /**
     * Set the form's model and render the opening tag
     * @param $model
     * @param array $attributes
     * @return string
     */
    public function model($model, array $attributes = array())
    {
        $this->model = $model;
        return $this->open($attributes);
    }

    /**
     * Render to form's opening tag
     * @param array $attributes
     * @return string
     */
    public function open(array $attributes = array())
    {
        $this->mergeAttributes($attributes);
        return $this->getRenderer()->formOpen($this);
    }

    /**
     * Render the form's closing tag
     * @return string
     */
    public function close()
    {
        return $this->getRenderer()->formClose($this);
    }

    /**
     * Render the form, including the form's opening and closing tags
     * @param string $model
     * @param array $options
     * @return string
     */
    public function html($model = null, array $options = [])
    {
        if (is_array($model))
        {
            $options = $model; $model = null;
        }
        if ($model) $this->model($model);
        return $this->open($options).$this->render().$this->close();
    }

    /**
     * Render the form's fields
     *
     * @return string
     */
    public function render()
    {
        $output = '';

        $output .= $this->fire('beforeForm', $this);

        // Render a rowless form
        if ( sizeof($this->rows) == count($this->fields) )
        {
            $output .= $this->renderFields($this->fields);
        }
        else
        {
            $rows = $this->getFieldsByRow('_default');
            foreach ($rows as $rowId => $rowFields )
            {
                $row = array_pull($this->rows, $rowId, false);
                if ($row)
                    $output .= $this->renderRow($row, $rowFields);
                else
                    $output .= $this->renderFields($rowFields);
            }
            if (isset($fields['_default']))
            {
                $output .= $this->renderFields($fields['_default']);
            }
        }

        $output .= $this->fire('afterForm', $this);

        return $output;
    }

    /**
     * Returns an array of fields grouped by row
     * @param  string|null $default  fields without a row will be assigned to this key
     * @return array
     * [
     *   'rowId' => [$field, $field, ...],
     *   ...
     * ]
     */
    public function getFieldsByRow($default = '_default')
    {
        $sorted = $this->getFieldsByProperty('row', $default);
        return $sorted;
    }

    /**
     * Returns the field list broken up by a given setting.
     *
     * @param  string $property A field setting such as 'tab'. These will form
     *                         the keys of the associative array returned.
     * @param  string $default Default value to use if the setting doesn't exist
     *                         for a field.
     *
     * @return array
     * [
     *   'foo' => [$field, $field, ...],
     *   'bar' => [$field, $field, ...],
     *   ...
     * ]
     */
    protected function getFieldsByProperty($property, $default='')
    {
        $sorted = array();

        foreach ( $this->fields as $field )
        {
            $field_property = $field->getProperty($property, $default);
            $sorted[$field_property][] = $field;
        }

        return $sorted;
    }

    /**
     * Render a list of fields.
     * @param Element $row
     * @param array|Field[] $fields
     * @return string
     */
    protected function renderRow($row, $fields)
    {
        $count = count($fields);
        $output = $this->fire('beforeRow', $row, $fields);
        $output .= $this->getRenderer()->rowOpen($row, $fields);
        foreach ( $fields as $field )
        {
            $field->setProperty('rowSize', $count);
            $output .= $this->renderField($field);
        }
        $output .= $this->getRenderer()->rowClose($row, $fields);
        $output .= $this->fire('afterRow', $row, $fields);
        return $output;
    }

    /**
     * Render a list of fields.
     *
     * @param  array|Field[] $fields
     * @return string
     */
    protected function renderFields($fields)
    {
        $output = '';
        foreach ( $fields as $field )
        {
            if ($field->skip) continue;
            $output .= $this->renderField($field);
        }
        return $output;
    }

    /**
     * Render a given field.
     *
     * @param  Field  $field
     *
     * @return string
     */
    public function renderField(Field $field)
    {
        $output = '';

        if ($this->autoLabels && !$field->label)
            $field->label = Str::title(str_replace('_', ' ', $field->slug));
        if ($this->fieldNames)
            $field->addName($this->fieldNames, true);

        $output .= $this->fire('beforeField', $this, $field);

        if ($this->manager->isMacro($field->type))
            $fieldHtml = $this->manager->callMacro($field->type, $field, $this->getRenderer());
        else
            $fieldHtml = $this->getRenderer()->field($field);

        $output .= $fieldHtml;

        $output .= $this->fire('afterField', $this, $field);

        return $output;
    }

    /**
     * @return FormRenderer
     */
    public function getRenderer()
    {
        if (!$this->_renderer)
        {
            $this->_renderer = $this->manager->getRenderer($this->rendererName);
            $this->_renderer->setFormBinders($this);
        }
        return $this->_renderer;
    }

    /**
     * @param $type
     * @return bool
     */
    public function isValidType($type)
    {
        return $this->manager->isMacro($type) || $this->getRenderer()->isValidType($type);
    }

    /**
     * @param mixed $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return \Illuminate\Support\Collection|\Flynsarmy\FormBuilder\Element[]
     */
    public function getRows()
    {
        return new Collection($this->rows);
    }

    /**
     * @return \Illuminate\Support\Collection|\Flynsarmy\FormBuilder\Field[]
     */
    public function getFields()
    {
        return new Collection($this->fields);
    }

    /**
     * @return array|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * @return array
     */
    public function getFieldNames()
    {
        return $this->fieldNames;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return array
     */
    public function getFieldAttributeBuffer()
    {
        return $this->fieldAttributeBuffer;
    }

    /**
     * @return array
     */
    public function getFieldPropertyBuffer()
    {
        return $this->fieldPropertyBuffer;
    }

    /**
     * @return string
     */
    public function getRendererName()
    {
        return $this->rendererName;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function autoLabels($value = true)
    {
        $this->autoLabels = (bool) $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function autoLabelsEnabled()
    {
        return $this->autoLabels;
    }

    /**
     * Dynamically set properties and settings
     * calling $form->add{Fieldtype}($slug [,$label]) will add a new field to the form
     *
     * @param  string $name      Setting name
     * @param  array  $arguments Setting value(s)
     *
     * @return $this|\Flynsarmy\FormBuilder\Field
     */
    public function __call($name, $arguments)
    {
        if (preg_match("/add([A-Z][\w]+)/", $name, $matched))
        {
            $type = lcfirst($matched[1]);
            $slug = array_get($arguments, 0);
            $field = $this->add($slug, $type);
            $label = array_get($arguments, 1);
            if ($label)
                $field->label($label);
            return $field;
        }
        if ( !sizeof($arguments) )
            $this->set($name, true);
        elseif ($name == 'class')
            $this->addClass($arguments);
        elseif ( sizeof($arguments) == 1 )
            $this->setAttr($name, $arguments[0]);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}