@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $store = $storefrontCompany?->ecommerce_slug ?: 'techforge';
@endphp
@extends('ecommerce::admin.layout', ['title' => 'Storefront Listings', 'heading' => 'Storefront Listings'])

@section('content')
<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px"><p class="muted">Each listing attaches an active, Manufacturing-managed BOM.</p><a class="button" href="{{ route('ecommerce.admin.listings.create') }}">+ Add listing</a></div>
    <table><thead><tr><th>SKU</th><th>Name</th><th>Status</th><th>Available</th><th>Price</th><th></th></tr></thead><tbody>
        @forelse ($listings as $listing)
            <tr>
                <td>{{ $listing->sku }}</td>
                <td>
                    @if($listing->image_url)
                        <img src="{{ asset('storage/' . $listing->image_url) }}" alt="{{ $listing->name }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; vertical-align: middle; margin-right: 8px;">
                    @endif
                    {{ $listing->name }}
                </td>
                <td>{{ ucfirst($listing->status) }}</td>
                <td>{{ $listing->available_quantity }}</td>
                <td>&#8369;{{ number_format((float) $listing->price, 2) }}</td>
                <td>
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                        <a class="button alt" href="{{ route('ecommerce.admin.listings.edit', $listing) }}">Edit</a>
                        <form method="post" action="{{ route('ecommerce.admin.listings.destroy', $listing) }}" onsubmit="return confirm('Are you sure you want to delete this listing?');">
                            @csrf
                            @method('delete')
                            <button class="button alt" style="color:red;border-color:red;cursor:pointer;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No listings yet.</td></tr>
        @endforelse
    </tbody></table>
</section>
@endsection
