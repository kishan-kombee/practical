<?php

namespace App\Console\Commands;

use App\Jobs\ImportRoleJob;
use Illuminate\Console\Command;

class ImportRoleCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:Role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import roles through file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Dispatches the ImportRoleJob for processing.
        ImportRoleJob::dispatch();
    }
}
