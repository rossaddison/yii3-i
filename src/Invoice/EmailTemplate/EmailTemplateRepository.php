<?php
declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;
use App\Invoice\Setting\SettingRepository;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class EmailTemplateRepository extends Select\Repository
{
    private EntityWriter $entityWriter;
    
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * @throws Throwable
     */
    public function save(EmailTemplate $emailtemplate): void
    {
        $this->entityWriter->write([$emailtemplate]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(EmailTemplate $emailtemplate): void
    {
        $this->entityWriter->delete([$emailtemplate]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'email_template_title', 'email_template_from_name', 'email_template_from_email'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoEmailTemplateCount(string $email_template_id) : int {
        $count = $this
            ->select()
            ->where(['id' => $email_template_id])
            ->count();
        return  $count;        
    }
    
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function repoEmailTemplatequery(string $email_template_id):object|null
    {
        $query = $this
            ->select()
            ->where(['id' => $email_template_id]);
        return  $query->fetchOne() ?: null;        
    }
        
     /**
     * @psalm-return EntityReader
     */
    public function repoEmailTemplateType(string $email_template_type): EntityReader
    {
        $query = $this
            ->select()
            ->where(['email_template_type' => $email_template_type]);
        return $this->prepareDataReader($query);      
    }
    
    public static function getSettings(SettingRepository $setting): SettingRepository
    {
        $setting->load_settings();
        return $setting;
    }
    
    /**
     * @psalm-param 'pdf' $type
     */
    public function get_invoice_templates(string $type)
    {
        $pdf_template_directory = dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/template/invoice/pdf'; 
        $public_template_directory = dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/template/invoice/public';
        $php_only = (new PathMatcher())
        ->doNotCheckFilesystem()
        ->only('*.php');
        if ($type === 'pdf') {
              $templates = FileHelper::findFiles($pdf_template_directory, [
                                        'filter' => $php_only,
                                        'recursive' => false,
                                ]);
        } elseif ($type === 'public') {
              $templates = FileHelper::findFiles($public_template_directory, [
                                        'filter' => $php_only,
                                        'recursive' => false,
                                ]);
        }
        $extension_remove= $this->remove_extension($templates);
        $templates = $this->remove_path($extension_remove);
        return $templates;
    }
    
    /**
     * @psalm-param 'pdf' $type
     */
    public function get_quote_templates(string $type): array
    {
        $pdf_template_directory = dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/template/quote/pdf'; 
        $public_template_directory = dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/template/quote/public';
        $pdf_only = (new PathMatcher())
        ->doNotCheckFilesystem()
        ->only('*.pdf');
        if ($type === 'pdf') {
            $templates = FileHelper::findFiles($pdf_template_directory, [
                                        'filter' => $pdf_only,
                                        'recursive' => false,
                                ]);
        } elseif ($type === 'public') {
            $templates = FileHelper::findFiles($public_template_directory, [
                                        'filter' => $pdf_only,
                                        'recursive' => false,
                                ]);
        }
        $extension_remove = $this->remove_extension($templates);
        $templates = $this->remove_path($extension_remove);
        return $templates;
    }

    private function remove_extension(array $files) : array
    {
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }
        return $files;
    }
    
    private function remove_path(array $files) : array
    {
        //https://stackoverflow.com/questions/1418193/how-do-i-get-a-file-name-from-a-full-path-with-php
        foreach ($files as $key => $file) {
            $files[$key] = basename($file);
        }
        return $files;
    }
            
    private function flat_an_array(array $a) : array
    {
        foreach($a as $i)
        {
            if(is_array($i)) 
            {
                if($na) $na = array_merge($na, $this->flat_an_array($i));
                else $na = $this->flat_an_array($i);
            }
            else $na[] = $i;
        }
        return $na;
    }
}
