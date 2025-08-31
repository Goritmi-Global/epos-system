<?php 
namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\POS\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Backend/Payment/Index');   
        // $payments = $this->service->list($request->only('q','method'));
        // return Inertia::render('Payment/Index', ['payments'=>$payments]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'=>'required|exists:orders,id',
            'method'=>'required|string|max:50',
            'amount'=>'required|numeric|min:0',
            'reference'=>'nullable|string|max:255',
        ]);
        $this->service->create($validated);
        return back()->with('success','Payment recorded');
    }
}
