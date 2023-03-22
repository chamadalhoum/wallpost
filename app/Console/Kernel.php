<?php

namespace App\Console;


use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\RelanceGmb::class,

        Commands\AvisCron::class,
        Commands\FicheCron::class,     
        Commands\PhotoCron::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command( 'quote:daily')->everyMinute()->appendOutputTo(storage_path('logs/quote.log'));
        //$schedule->command('avis:cron')->dailyAt('10:30')->appendOutputTo(storage_path('logs/avis.log'));
        //$schedule->command('photo:cron')->dailyAt('16:00')->appendOutputTo(storage_path('logs/photo.log'));
      //  $schedule->command('post:cron')->dailyAt('09:00')->appendOutputTo(storage_path('logs/post.log'));
     //   $schedule->command('fiche:cron')->dailyAt('09:00')->appendOutputTo(storage_path('logs/fiche.log'));
       // $schedule->job(new CronFicheMybusines())->daily('09:00')->appendOutputTo(storage_path('logs/fiche.log'));
    //   $schedule->command('statistique:cron')->dailyAt('09:02')->appendOutputTo(storage_path('logs/statistique.log'));
    
        //$schedule->job(new CronPostMybusines())->dailyAt('08:27')->appendOutputTo(storage_path('logs/CronPost.log'));
     //  $schedule->command('poststate:cron')->dailyAt('13:20')->appendOutputTo(storage_path('logs/poststate.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
 $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
