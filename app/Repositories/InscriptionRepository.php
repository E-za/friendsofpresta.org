<?php
namespace App\Repositories;

use App\Models\Inscription;
use App\Http\Requests\InscriptionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class InscriptionRepository
{
    public static $status = [
        'A attribuer' => [
            'color' => 'warning'
        ],
        'création en cours' => [
            'color' => 'info'
        ],
        'en ligne' => [
            'color' => 'success'
        ],
        'abandonné' => [
            'color' => 'dark'
        ]
    ];

    public static function getStatus()
    {
        return self::$status;
    }

    /**
     * Save an inscription
     *
     * @param InscriptionRequest $request
     * @return bool
     */
    public function save(InscriptionRequest $request)
    {
        $inscription = isset($request->inscription) ? $request->inscription : new Inscription();
        $validated = $request->validated();

        // Insert inscription in DB
        $inscription->fill($validated)->save();

        // Send email notification
        Mail::send('emails.notif', ['validated' => $validated], function($message) use ($validated) {
            $message
                ->to('contact@ecommerce-solidaire.fr', 'Ecommerce Solidaire')
                ->subject("Contact via le mini-site Ecommercesolidaire");
        });

        // Send email Welcome
        Mail::send('emails.welcome', [], function($message) use ($validated) {
            $message
                ->to($validated['email'], $validated['prenom'] . ' ' . $validated['nom'])
                ->subject("Bienvenue dans l'initiative e-commerce solidaire");
        });

        return true;
    }

    /**
     * Update an inscription
     *
     * @param InscriptionRequest $request
     * @param Inscription $inscription
     * @return bool
     */
    public function update(InscriptionRequest $request, Inscription $inscription)
    {
        $inscription->fill($request->validated())->save();

        return true;
    }


    /**
     * @return mixed
     */
    public function getOnline()
    {
        return Inscription::where('status', 'en ligne')->orderBy('cp')->get();
    }

    /**
     * @param int $perPage
     *
     * @return mixed
     */
    public function getUsersRankings($perPage = 15)
    {
        return DB::table('inscriptions')->select(
            'users.name',
            'users.company',
            DB::raw('COUNT(IF(inscriptions.status="création en cours",inscriptions.id, NULL)) as inprogress'),
            DB::raw('COUNT(IF(inscriptions.status="en ligne",inscriptions.id, NULL)) AS online'),
            DB::raw('COUNT(inscriptions.id) AS total')
        )
            ->join('users', 'users.id', '=', 'inscriptions.attribution')
            ->whereNotIn('inscriptions.status', ['abandonné', 'A attribuer'])
            ->groupBy('users.name', 'users.company')
            ->orderBy('total', 'desc')
            ->paginate($perPage);
    }

    /**
     * @param int $cache Cache duration in second
     *
     * @return mixed
     */
    public function getStatsShops($cache = 3600)
    {
        return Cache::remember('ecommerce-solidaire.stats-shops', $cache, function () {
            $stats = [
                'total' => 0,
                'ca' => 0
            ];

            $db = DB::connection('mysql_global');
            foreach($db->select('SHOW DATABASES') as $database) {
                if($db->table('information_schema.tables')->select('table_schema')->where('table_schema', $database->Database)->where('table_name', 'ps_orders')->count()) {
                    $result = $db
                        ->table(DB::raw('`' . $database->Database.'`.ps_orders'))
                        ->select(DB::raw('COUNT(*) as total, SUM(total_products_wt) as ca'))
                        ->first();

                    $stats['total'] += $result->total;
                    $stats['ca'] += $result->ca;
                }
            }

            return $stats;
        });
    }
}