<?php
namespace Zkeene\LiveCrud\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Crud extends GeneratorCommand
{
    protected $signature = 'crud:make {name}';

    protected $description = 'Generate Crud Livewire';
    protected $search = '$this->search';
    public function handle()
    {
        parent::handle();

        if (!is_dir(app_path('http/livewire'))) {
            mkdir(app_path('http/livewire'));
        }
        $componentPath = app_path('http/livewire/') . $this->arguments()['name'] . 'LiveComponent.php';
        $content = file_get_contents($this->getStub());

        // Update the file content with additional data (regular expressions)
        $this->info('Generating Livewire Component');

        $content = $this->buildContent($content);
        file_put_contents($componentPath, $content);
        $this->info('Livewire Component Generated');

        $this->info('Generating View');

        Artisan::call('crud:view', ['name' => $this->arguments()['name']]);
    }

    public function buildContent($content)
    {
        $array = [
            '{{ namespace }}' => $this->getDefaultNamespace('App'),
            '{{ name }}' => ucfirst($this->arguments()['name']),
            '{{ blade }}' => Str::slug($this->arguments()['name']),
            '{{ properties }}' => $this->buildProperties(),
            '{{ useclasses }}' => $this->usedClasses(),
            '{{ setedibleval }}' => $this->setEditableValues(),
            '{{ codeTostore }}' => $this->getStoreCode(),
            '{{ codeToupdate }}' => $this->getUpdateCode(),
            '{{ rules }}' => $this->getRules(),
            '{{ query }}' => $this->search(),
            '{{ reset }}' => $this->resetForms()
        ];
        return str_replace(array_keys($array), array_values($array), $content);
    }

    public function resetForms()
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $str = '';
        $columnCount = count($columns);
        $padding = '        ';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c == 1) {
                    if ($c == $columnCount) {
                        $str .= '$this->'.str_replace('-', '_', Str::slug($column)) . ' = "";';
                    } else {
                        $str .= '$this->'.str_replace('-', '_', Str::slug($column)) . ' = "";' . PHP_EOL;
                    }
                } else {
                    if ($c == $columnCount) {
                        $str .= $padding . '$this->'.str_replace('-', '_', Str::slug($column)) . ' = "";';
                    } else {
                        $str .= $padding . '$this->'.str_replace('-', '_', Str::slug($column)) . ' = "";' . PHP_EOL;
                    }
                }
            }
            $c++;
        }
        return $str;
    }

    public function search()
    {
        $name = $this->arguments()['name'];
        $class = 'App\\Models\\' . $name;
        $model = new $class;
        $singular_lower = Str::of($name)->lower();
        $plural_lower = Str::of($name)->plural()->lower();
        $columns = $model->getFillable();
        $columnCount = count($columns);
        $str = '$' . $plural_lower . ' = ' . $name . '::';
        $i = 1;
        $padding2 = '        ';
        $padding3 = '            ';
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at' || $column!='password') {
                if ($i == 1) {
                    $str .= "where('". $column . "', 'like', '%'.$this->search.'%')". PHP_EOL;
                } else {
                        $str .= $padding3 . "->orWhere('" . $column . "', 'like', '%'.$this->search.'%')" . PHP_EOL;
                }
            }
            $i++;
        }
        $str .= $padding3 . '->latest()->paginate($this->paginate);' . PHP_EOL;
        $str .= $padding2 . "return view('livewire.$singular_lower', ['$plural_lower'=> $$plural_lower]);";
        return $str;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Livewire';
    }

    public function getRules()
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        $columns = $model->getFillable();
        $str = '';
        $onetab = '    ';
        $twotabs = '        ';
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                $str .= $twotabs . "'" . str_replace('-', '_', Str::slug($column)) . "' => 'required'," . PHP_EOL;
            }
        }
        return 'protected $rules = [' . PHP_EOL . $str . $onetab . '];';
    }

    public function buildProperties(): string
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        $model = new $class;
        if (!class_exists($class)){
            throw new \Exception('Model Not Found. Please Check if Model Exists at -'.$class);
        }
        $columns = $model->getFillable();
        $columnCount = count($columns);
        $str = '';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c == 1) {
                    if($c == $columnCount) {
                        $str .= 'public $' . str_replace('-', '_', Str::slug($column)) . ';';
                    } else {
                        $str .= 'public $' . str_replace('-', '_', Str::slug($column)) . ';' . PHP_EOL;
                    }
                } else {
                    if($c == $columnCount) {
                        $str .= '    public $' . str_replace('-', '_', Str::slug($column)) . ';';
                    } else {
                        $str .= '    public $' . str_replace('-', '_', Str::slug($column)) . ';' . PHP_EOL;
                    }
                }
            }
            $c++;
        }
        return $str;
    }

    public function setEditableValues()
    {
        $name = $this->arguments()['name'];
        $class = 'App\\Models\\' . $name;
        $model = new $class;
        $columns = $model->getFillable();
        $columnCount = count($columns);
        $lower = Str::of($name)->lower();
        $str = '$' . $lower . ' = ' . $name . '::find($primaryId);' . PHP_EOL . PHP_EOL;
        $padding = '        ';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                if ($c == $columnCount) {
                        $str .= $padding . '$this->' . str_replace('-', '_', Str::slug($column)) . ' = $' . $lower . '->' . $column . ';';
                } else {
                        $str .= $padding. '$this->' . str_replace('-', '_', Str::slug($column)) . ' = $' . $lower . '->' . $column . ';' . PHP_EOL;
                }
            }
            $c++;
        }
        return $str;
    }

    public function getStoreCode()
    {
        $name = $this->arguments()['name'];
        $class = 'App\\Models\\' . $name;
        $lower = Str::of($name)->lower();
        $model = new $class;
        $columns = $model->getFillable();
        $str = '$' . $lower. ' = new ' . $name . '();' . PHP_EOL . PHP_EOL;
        $padding = '        ';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                $str .= $padding . '$' . $lower . '->' . str_replace('-', '_', Str::slug($column)) . '= $this->' . str_replace('-', '_', Str::slug($column)) . ';' . PHP_EOL;
            }
            $c++;
        }
        $str .= PHP_EOL . $padding . '$' . $lower . '->save();';
        return $str;
    }

    public function getUpdateCode()
    {
        $name = $this->arguments()['name'];
        $class = 'App\\Models\\' . $name;
        $lower = Str::of($name)->lower();
        $model = new $class;
        $columns = $model->getFillable();
        $str = '$' . $lower. ' = ' . $name . '::find($this->primaryId);' . PHP_EOL . PHP_EOL;
        $padding = '        ';
        $c = 1;
        foreach ($columns as $column) {
            if ($column != 'created_at' || $column != 'updated_at') {
                $str .= $padding . '$' . $lower . '->' . str_replace('-', '_', Str::slug($column)) . '= $this->' . str_replace('-', '_', Str::slug($column)) . ';' . PHP_EOL;
            }
            $c++;
        }
        $str .= PHP_EOL . $padding . '$' . $lower . '->save();';

        return $str;
    }

    protected function usedClasses()
    {
        return 'use Livewire\Component;' . PHP_EOL . 'use Livewire\WithPagination;' . PHP_EOL . 'use App\\Models\\' . $this->arguments()['name'] . ';';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $this->checkIfModelExists();

        if (file_exists(base_path() . '/stubs/crud.php.stub')){
            return base_path() . '/stubs/crud.php.stub';
        }
        return base_path().'/vendor/zkeene/livecrud/src/stubs/crud.php.stub';
    }

    public function checkIfModelExists()
    {
        $class = 'App\\Models\\' . $this->arguments()['name'];
        if (!class_exists($class)){
            throw new \Exception('Model Not Found. Please Check if Model Exists at -'.$class);
        }
    }

}
