<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $customer_code
 * @property string $name
 * @property string|null $company_name
 * @property string $phone
 * @property string|null $whatsapp_number
 * @property string|null $email
 * @property string|null $address_line1
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property int|null $first_partner_id
 * @property int|null $lead_id
 * @property int|null $created_by
 * @property string $status
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\Partner|null $firstPartner
 * @property-read \App\Models\Lead|null $lead
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerPayment> $payments
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'name',
        'company_name',
        'phone',
        'whatsapp_number',
        'email',
        'address_line1',
        'city',
        'state',
        'postal_code',
        'country',
        'first_partner_id',
        'lead_id',
        'created_by',
        'status',
        'notes',
    ];

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function firstPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'first_partner_id');
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<CustomerPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }
}
