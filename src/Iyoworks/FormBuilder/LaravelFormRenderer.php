<?php namespace Iyoworks\FormBuilder;


use Iyoworks\FormBuilder\Traits\Bindable;
use Illuminate\Html\FormBuilder as Builder;
use Illuminate\Html\HtmlBuilder;

class LaravelFormRenderer implements FormRenderer
{
    /**
     * @var Builder
     */
    protected $builder;
    /**
     * @var \Illuminate\Html\HtmlBuilder
     */
    private $htmlBuilder;

    public function __construct(Builder $builder, HtmlBuilder $htmlBuilder)
    {
        $this->builder = $builder;
        $this->htmlBuilder = $htmlBuilder;
    }

    /**
     * @param $type
     * @return bool
     */
    public function isValidType($type)
    {
        return method_exists($this, $type.'Field');
    }

    /**
     * Add binders to the form
     * @param Form $form
     */
    public function setFormBinders(Form $form)
    {

    }


    /**
     * @param Form $form
     * @return mixed
     */
    public function formOpen(Form $form)
    {
        $options = $form->getAttributes();
        $options[$form->getActionType()] = $form->getAction();
        if ($model = $form->getModel())
            return $this->builder->model($model, $options);
        return $this->builder->open($options);
    }

    /**
     * @param Form $form
     * @return string
     */
    public function formClose(Form $form)
    {
        return $this->builder->close();
    }

    /**
     * @param Element $row
     * @return string
     */
    public function rowOpen(Element $row)
    {
        $_atts = $this->htmlBuilder->attributes($row->getAttributes());
        return '<div'.$_atts.'>';
    }

    /**
     * @param Element $row
     * @return string
     */
    public function rowClose(Element $row)
    {
        return'</div>';
    }


    /**
     * Render a given field.
     *
     * @param  Field  $field
     *
     * @return string
     */
    public function field(Field $field)
    {
        if (!$this->isValidType($field->type))
            return $this->inputField($field);
        return $this->{$field->type.'Field'}($field);
    }

    /**
     * @return string
     */
    public function tokenField()
    {
        return $this->builder->token();
    }

    /**
     * @param Field $field
     * @return string
     */
    public function labelField(Field $field)
    {
        return $this->builder->label($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function inputField(Field $field)
    {
        return $this->builder->input($field->type, $field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function textField(Field $field)
    {
        return $this->builder->text($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function passwordField(Field $field)
    {
        return $this->builder->password($field->name, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function hiddenField(Field $field)
    {
        return $this->builder->hidden($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function emailField(Field $field)
    {
        return $this->builder->email($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function urlField(Field $field)
    {
        return $this->builder->url($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function fileField(Field $field)
    {
        return $this->builder->file($field->name, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function textareaField(Field $field)
    {
        return $this->builder->textarea($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function selectField(Field $field)
    {
        return $this->builder->select($field->name, $field->options ?: [], $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function selectRangeField(Field $field)
    {
        return $this->builder->selectRange($field->name, $field->begin, $field->end, $field->selected, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function selectYearField(Field $field)
    {
        return $this->selectRange($field);
    }

    /**
     * @param Field $field
     * @return string
     */
    public function selectMonthField(Field $field)
    {
        return $this->builder->selectMonth($field->name, $field->selected, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function checkboxField(Field $field)
    {
        return $this->builder->checkbox($field->name, $field->value, $field->checked, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function radioField(Field $field)
    {
        return $this->builder->radio($field->name, $field->value,$field->checked, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function resetField(Field $field)
    {
        return $this->builder->reset($field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function imageField(Field $field)
    {
        return $this->builder->image($field->url, $field->name, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function submitField(Field $field)
    {
        return $this->builder->submit($field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function buttonField(Field $field)
    {
        return $this->builder->button($field->value, $field->getAttributes());
    }

} 