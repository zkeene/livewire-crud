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
            '{{ headings }}' => $this->getHeadings(),
            '{{ renderedData }}' => $this->getRenderedData(),
            '{{ form }}' => $this->getForm()
        ];

        return str_replace(array_keys($array), array_values($array), $content);
    }

    public function getForm()
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $str = '';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c == 1) {
                    $str .= $this->makeInput($column) . PHP_EOL;
                } else {
                    $str .= $this->makeInput($column) . PHP_EOL;
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
            return "<div><label class='block'><span class='text-gray-700 @error('{$name}') text-red-500  @enderror'>{$label}</span><input type='{$type}' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('{$name}')  border-red-500 @enderror focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50' wire:model='{$name}'>@error('{$name}')<span class='text-red-500 text-sm'>{$message}</span>@enderror</label></div>";
    }

    public function getType($name)
    {
        if (in_array(strtolower($name), $this->emailNames)) {
            return 'email';
        }
        if (strtolower($name) == 'password') {
            return 'password';
        }
        return 'text';
    }

    public function getRenderedData()
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $str = '';
        $c = 1;
        $str .= '@forelse($rows as $row)' . PHP_EOL;
        $str .= '<tr>';
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c == 1) {
                    $str .= $this->getDynamicData(str_replace('-', ' ', Str::slug($column))) . PHP_EOL;
                } else {
                    $str .= $this->getDynamicData(str_replace('-', ' ', Str::slug($column))) . PHP_EOL;
                }
            }
            $c++;
        }
        $str .= '<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="#" class="text-indigo-600 hover:text-indigo-900" wire:click="edit({{ $row->id }})">{{ __("Edit") }}</a>
                            <a href="#" class="text-indigo-600 hover:text-indigo-900" wire:click="confirmDelete({{ $row->id }})">{{ __("Delete") }}</a>
                        </td></tr>';
        $str .= '@empty  <tr><td>No Records Found</td></tr>   @endforelse' . PHP_EOL;
        return $str;
    }

    public function getDynamicData($name): string
    {
        return ' <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->' . $name . '}}</td>' . PHP_EOL;
    }

    public function getHeadings(): string
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $columnCount = count($columns);
        $c = 1;
        $str = '';
        $padding = '                            ';
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c==1) {
                    $str .= $this->getInput(str_replace('-', ' ', Str::slug($column)));
                } else {
                    $str .= $padding . $this->getInput(str_replace('-', ' ', Str::slug($column)));
                }
            }
            $c++;
        }
        return $str;
    }

    public function getInput($name): string
    {
        return '<th scope="col" class="px-6 py-3">' . $name . '</th>' . PHP_EOL;
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
