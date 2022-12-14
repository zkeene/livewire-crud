<?php

namespace Zkeene\LiveCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LiveCrudView extends GeneratorCommand
{
    protected $signature = 'crud:view {name}';

    protected $description = 'Generate View For Crud Command';

    protected $emailNames = [
        'email',
        'email_address',
    ];

    public function tabs($i)
    {
        return str_repeat('    ', $i);
    }

    public function handle()
    {
        if (!is_dir(resource_path('views/livewire'))) {
            mkdir(resource_path('views/livewire'));
        }
        $viewPath = resource_path('views/livewire/') . strtolower($this->arguments()['name']) . '.blade.php';
        $content = file_get_contents($this->getStub());
        $content = $this->buildContent($content);
        file_put_contents($viewPath, $content);

        $this->info('View Generated');
    }

    public function buildContent($content)
    {
        $array = [
            '{{ title }}' => ucfirst($this->arguments()['name']),
            '{{ nameLower }}' => Str::of($this->arguments()['name'])->lower(),
            '{{ nameLowerPlural }}' =>Str::of($this->arguments()['name'])->lower()->plural(),
            '{{ headings }}' => $this->getHeadings(),
            '{{ renderedData }}' => $this->getRenderedData(),
            '{{ form }}' => $this->getForm(),
            '{{ viewModal }}' => $this->getViewModal()
        ];

        return str_replace(array_keys($array), array_values($array), $content);
    }

    public function getForm()
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $columnCount = count($columns);
        $str = '';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c != 1) {
                    $str .= $this->tabs(10);
                }

                if ($this->getType($column) == 'foreignid') {
                    $str .= $this->makeSelect($column);
                } else {
                    $str .= $this->makeInput($column);
                }

                if ($c != $columnCount) {
                    $str .= PHP_EOL;
                }
            }
            $c++;
        };
        return $str;
    }

    public function makeInput($name)
    {
        $type = $this->getType($name);
        $label = ucfirst(str_replace('-', ' ', Str::slug($name)));
        $message = '{{ $message }}';
        $output = "<div><label class='block'><span class='text-gray-700 @error('{$name}') text-red-500  @enderror'>{$label}</span>";
        $output .= "<input type='{$type}' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('{$name}') border-red-500 @enderror focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50' wire:model='{$name}'>";
        $output .= "@error('{$name}')<span class='text-red-500 text-sm'>{$message}</span>@enderror</label></div>";
        return $output;
    }

    public function makeSelect($name)
    {
        $label = ucfirst(str_replace('-', ' ', Str::slug(substr($name, 0, -3))));
        $pluralLower = Str::of($label)->plural()->lower();
        $singularLower = Str::of($label)->singular()->lower();
        $message = '{{ $message }}';

        $class = 'App\\Models\\' . $label;
        $model = new $class;
        $displayField = $model->crudInfo['displayField'];

        $output = "<div><label class='block'><span class='text-gray-700 @error('{$name}') text-red-500  @enderror'>{$label}</span>";
        $output .= "<select class='mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('{$name}') border-red-500 @enderror focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50' wire:model='{$name}'>" . PHP_EOL;
        $output .= $this->tabs(10) . '@foreach($' . $pluralLower . ' as $' . $singularLower . ')' . PHP_EOL;
        $output .= $this->tabs(10) . '<option value="{{ $' . $singularLower . '->id }}" wire:key="' . $singularLower . '-{{ $' . $singularLower . '->id }}">{{ $' . $singularLower . '->' . $displayField . ' }}</option>'. PHP_EOL;
        $output .= $this->tabs(10) . "@endforeach" . PHP_EOL;
        $output .= $this->tabs(10) . "</select>" . PHP_EOL;
        $output .= $this->tabs(10) . "@error('{$name}')<span class='text-red-500 text-sm'>{$message}</span>@enderror</label></div>";

        return $output;
    }

    public function getViewModal () {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $columnCount = count($columns);
        $str = '';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c != 1) {
                    $str .= $this->tabs(10);
                }

                $str .= $this->makeViewLine($column);

                if ($c != $columnCount) {
                    $str .= PHP_EOL;
                }
            }
            $c++;
        };
        return $str;
    }

    public function makeViewLine($name)
    {
        $type = $this->getType($name);
        if ($type == 'foreignid'){
            $name = substr($name, 0,-3);
        }
        $label = ucfirst(str_replace('-', ' ', Str::slug($name)));
        
        $output = "<div><label class='block'><span class='text-gray-700'>{$label}</span>";
        $output .= "<input type='text' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm' disabled wire:model='{$name}'>";
        $output .= '</label></div>';

        return $output;
    }

    public function getType($name)
    {
        if (in_array(strtolower($name), $this->emailNames)) {
            return 'email';
        }
        if (strtolower($name) == 'password') {
            return 'password';
        }
        if (substr($name, -3) == '_id') {
            return 'foreignid';
        }
        return 'text';
    }

    public function getRenderedData()
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $columnCount = count($columns);
        $str = '';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c != 1) {
                    $str .= $this->tabs(9);
                }

                $str .= $this->getDynamicData($column);

                if ($c != $columnCount) {
                    $str .= PHP_EOL;
                }
            }
            $c++;
        }
        return $str;
    }

    public function getDynamicData($column): string
    {
        $modelName = Str::of($this->arguments()['name'])->lower();

        if ($this->getType($column) == 'foreignid') {
            $class = 'App\\Models\\' . ucfirst(substr($column, 0, -3));
            $model = new $class;
            $displayField = $model->crudInfo['displayField'];
            return '<td class="px-6 py-4 whitespace-nowrap">{{ $'. $modelName . '->' . substr($column, 0, -3) . '->' . $displayField . '}}</td>';
        } else {
            return '<td class="px-6 py-4 whitespace-nowrap">{{ $'. $modelName . '->' . $column . ' }}</td>';
        }
    }

    public function getHeadings(): string
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $columnCount = count($columns);
        $c = 1;
        $str = '';
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($this->getType($column) == 'foreignid') {
                    $column = substr($column, 0, -3);
                }
                $heading = str_replace('-', ' ', Str::slug($column));
                if ($c!=1) {
                    $str .= $this->tabs(7);
                }
                $str .= $this->getInput($heading);
                if ($c != $columnCount) {
                    $str .= PHP_EOL;
                }
            }
            $c++;
        }
        return $str;
    }

    public function getInput($name): string
    {
        return '<th scope="col" class="px-6 py-3">' . $name . '</th>';
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     * @throws \Exception
     * @throws \Exception
     */
    protected function getStub()
    {
        if (file_exists(base_path() . '/stubs/view.php.stub')) {
            return base_path() . '/stubs/view.php.stub';
        }
        return base_path() . '/vendor/zkeene/livecrud/src/stubs/view.php.stub';
    }

}
