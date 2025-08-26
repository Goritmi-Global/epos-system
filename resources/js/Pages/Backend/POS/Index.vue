<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted } from "vue";

/* Categories bar (single row) */
const categories = [
  { key: "fruits",      label: "Fruits",      icon: "/assets/img/product/product62.png" },
  { key: "headphone",   label: "Headphones",  icon: "/assets/img/product/product63.png" },
  { key: "Accessories", label: "Accessories", icon: "/assets/img/product/product64.png" },
  { key: "Shoes",       label: "Shoes",       icon: "/assets/img/product/product65.png" },
  { key: "computer",    label: "Computer",    icon: "/assets/img/product/product66.png" },
  { key: "Snacks",      label: "Snacks",      icon: "/assets/img/product/product67.png" },
  { key: "watch",       label: "Watches",     icon: "/assets/img/product/product68.png" },
  { key: "cycle",       label: "Cycles",      icon: "/assets/img/product/product61.png" },
];

const activeCat = ref("fruits");
const setCat = (k) => (activeCat.value = k);
const isCat = (k) => activeCat.value === k;

/* Demo products per category (kept from your HTML) */
const productsByCat = {
  fruits: [
    { title: "Orange",     img: "/assets/img/product/product29.jpg", qty: 5,  price: 150.0, family: "Fruits" },
    { title: "Strawberry", img: "/assets/img/product/product31.jpg", qty: 1,  price: 15.0,  family: "Fruits" },
    { title: "Banana",     img: "/assets/img/product/product35.jpg", qty: 5,  price: 150.0, family: "Fruits" },
    { title: "Limon",      img: "/assets/img/product/product37.jpg", qty: 5,  price: 1500.0, family: "Fruits" },
    { title: "Apple",      img: "/assets/img/product/product54.jpg", qty: 5,  price: 1500.0, family: "Fruits" },
  ],
  headphone: [
    { title: "Earphones",  img: "/assets/img/product/product44.jpg", qty: 5, price: 150.0, family: "Headphones" },
    { title: "Earphones",  img: "/assets/img/product/product45.jpg", qty: 5, price: 150.0, family: "Headphones" },
    { title: "Earphones",  img: "/assets/img/product/product36.jpg", qty: 5, price: 150.0, family: "Headphones" },
  ],
  Accessories: [
    { title: "Sunglasses", img: "/assets/img/product/product32.jpg", qty: 1, price: 15.0,  family: "Accessories" },
    { title: "Pendrive",   img: "/assets/img/product/product46.jpg", qty: 1, price: 150.0, family: "Accessories" },
    { title: "Mouse",      img: "/assets/img/product/product55.jpg", qty: 1, price: 150.0, family: "Accessories" },
  ],
  Shoes: [
    { title: "Red nike",   img: "/assets/img/product/product60.jpg", qty: 1, price: 1500.0, family: "Shoes" },
  ],
  computer: [
    { title: "Desktop",    img: "/assets/img/product/product56.jpg", qty: 1, price: 1500.0, family: "Computers" },
  ],
  Snacks: [
    { title: "Duck Salad",      img: "/assets/img/product/product47.jpg", qty: 1, price: 1500.0, family: "Snacks" },
    { title: "Breakfast board", img: "/assets/img/product/product48.png", qty: 1, price: 1500.0, family: "Snacks" },
    { title: "California roll", img: "/assets/img/product/product57.jpg", qty: 1, price: 1500.0, family: "Snacks" },
    { title: "Sashimi",         img: "/assets/img/product/product58.jpg", qty: 1, price: 1500.0, family: "Snacks" },
  ],
  watch: [
    { title: "Watch",      img: "/assets/img/product/product49.jpg", qty: 1, price: 1500.0, family: "Watch" },
    { title: "Watch",      img: "/assets/img/product/product51.jpg", qty: 1, price: 1500.0, family: "Watch" },
  ],
  cycle: [
    { title: "Cycle",      img: "/assets/img/product/product52.jpg", qty: 1, price: 1500.0, family: "Cycle" },
    { title: "Cycle",      img: "/assets/img/product/product53.jpg", qty: 1, price: 1500.0, family: "Cycle" },
  ],
};

const visibleProducts = computed(() => productsByCat[activeCat.value] ?? []);

/* make bootstrap tooltips work inside modals (optional) */
onMounted(() => {
  const tipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  if (window.bootstrap) tipEls.forEach(el => new window.bootstrap.Tooltip(el));
});
</script>

<template>
  <Head title="POS Order" />

  <Master>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">POS Order</h2>
    </template>

    <div class="py-12">
      <div class="page-wrapper ms-0">
        <div class="content">
          <div class="row">
            <!-- LEFT: Categories + Products -->
            <div class="col-lg-8 col-sm-12">
              <div class="page-header">
                <div class="page-title">
                  <h4>Categories</h4>
                  <h6>Manage your purchases</h6>
                </div>
              </div>

              <!-- Categories: single horizontal row -->
              <ul class="tabs border-0" id="catTabs">
                <li
                  v-for="c in categories"
                  :key="c.key"
                  :class="{ active: isCat(c.key) }"
                  @click="setCat(c.key)"
                  role="button"
                >
                  <div class="product-details">
                    <img :src="c.icon" alt="" />
                    <h6>{{ c.label }}</h6>
                  </div>
                </li>
              </ul>

              <!-- Products grid (only selected category) -->
              <div class="row">
                <div class="col-lg-3 col-sm-6 d-flex" v-for="p in visibleProducts" :key="p.title">
                  <div class="productset flex-fill" :class="{ active: p.title === 'Orange' }">
                    <div class="productsetimg">
                      <img :src="p.img" alt="img" />
                      <h6>Qty: {{ p.qty.toFixed(2) }}</h6>
                      <div class="check-product"><i class="fa fa-check"></i></div>
                    </div>
                    <div class="productsetcontent">
                      <h5>{{ p.family }}</h5>
                      <h4>{{ p.title }}</h4>
                      <h6>{{ p.price.toFixed(2) }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- RIGHT: Order list -->
            <div class="col-lg-4 col-sm-12">
              <div class="order-list">
                <div class="orderid">
                  <h4>Order List</h4>
                  <h5>Transaction id : #65565</h5>
                </div>
                <div class="actionproducts">
                  <ul>
                    <li>
                      <a href="javascript:void(0);" class="deletebg"
                         data-bs-toggle="modal" data-bs-target="#delete">
                        <img src="/assets/img/icons/delete-2.svg" alt="img" />
                      </a>
                    </li>
                    <li>
                      <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="dropset">
                        <img src="/assets/img/icons/ellipise1.svg" alt="img" />
                      </a>
                      <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" data-popper-placement="bottom-end">
                        <li><a href="#" class="dropdown-item">Action</a></li>
                        <li><a href="#" class="dropdown-item">Another Action</a></li>
                        <li><a href="#" class="dropdown-item">Something Else</a></li>
                      </ul>
                    </li>
                  </ul>
                </div>
              </div>

              <div class="card card-order">
                <div class="card-body">
                  <div class="row">
                    <div class="col-12">
                      <a href="javascript:void(0);" class="btn btn-adds" data-bs-toggle="modal" data-bs-target="#create">
                        <i class="fa fa-plus me-2"></i>Add Customer
                      </a>
                    </div>
                    <div class="col-lg-12">
                      <div class="select-split">
                        <div class="select-group w-100">
                          <select class="select">
                            <option>Walk-in Customer</option>
                            <option>Chris Moris</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg-12">
                      <div class="select-split">
                        <div class="select-group w-100">
                          <select class="select">
                            <option>Product</option>
                            <option>Barcode</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    <div class="col-12 text-end">
                      <a class="btn btn-scanner-set">
                        <img src="/assets/img/icons/scanner1.svg" alt="img" class="me-2" />Scan bardcode
                      </a>
                    </div>
                  </div>
                </div>

                <div class="split-card"></div>

                <div class="card-body pt-0">
                  <div class="totalitem">
                    <h4>Total items : 4</h4>
                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete">Clear all</a>
                  </div>

                  <div class="product-table">
                    <!-- Row 1 -->
                    <ul class="product-lists">
                      <li>
                        <div class="productimg">
                          <div class="productimgs">
                            <img src="/assets/img/product/product30.jpg" alt="img" />
                          </div>
                          <div class="productcontet">
                            <h4>
                              Pineapple
                              <a href="javascript:void(0);" class="ms-2" data-bs-toggle="modal" data-bs-target="#edit">
                                <img src="/assets/img/icons/edit-5.svg" alt="img" />
                              </a>
                            </h4>
                            <div class="productlinkset"><h5>PT001</h5></div>
                            <div class="increment-decrement">
                              <div class="input-groups">
                                <input type="button" value="-" class="button-minus dec button" />
                                <input type="text" name="child" value="0" class="quantity-field" />
                                <input type="button" value="+" class="button-plus inc button" />
                              </div>
                            </div>
                          </div>
                        </div>
                      </li>
                      <li>3000.00</li>
                      <li>
                        <a data-bs-toggle="modal" data-bs-target="#delete" href="javascript:void(0);">
                          <img src="/assets/img/icons/delete-2.svg" alt="img" />
                        </a>
                      </li>
                    </ul>

                    <!-- Row 2 -->
                    <ul class="product-lists">
                      <li>
                        <div class="productimg">
                          <div class="productimgs">
                            <img src="/assets/img/product/product34.jpg" alt="img" />
                          </div>
                          <div class="productcontet">
                            <h4>
                              Green Nike
                              <a href="javascript:void(0);" class="ms-2" data-bs-toggle="modal" data-bs-target="#edit">
                                <img src="/assets/img/icons/edit-5.svg" alt="img" />
                              </a>
                            </h4>
                            <div class="productlinkset"><h5>PT001</h5></div>
                            <div class="increment-decrement">
                              <div class="input-groups">
                                <input type="button" value="-" class="button-minus dec button" />
                                <input type="text" name="child" value="0" class="quantity-field" />
                                <input type="button" value="+" class="button-plus inc button" />
                              </div>
                            </div>
                          </div>
                        </div>
                      </li>
                      <li>3000.00</li>
                      <li>
                        <a data-bs-toggle="modal" data-bs-target="#delete" href="javascript:void(0);">
                          <img src="/assets/img/icons/delete-2.svg" alt="img" />
                        </a>
                      </li>
                    </ul>

                    <!-- Row 3 -->
                    <ul class="product-lists">
                      <li>
                        <div class="productimg">
                          <div class="productimgs">
                            <img src="/assets/img/product/product35.jpg" alt="img" />
                          </div>
                          <div class="productcontet">
                            <h4>
                              Banana
                              <a href="javascript:void(0);" class="ms-2" data-bs-toggle="modal" data-bs-target="#edit">
                                <img src="/assets/img/icons/edit-5.svg" alt="img" />
                              </a>
                            </h4>
                            <div class="productlinkset"><h5>PT001</h5></div>
                            <div class="increment-decrement">
                              <div class="input-groups">
                                <input type="button" value="-" class="button-minus dec button" />
                                <input type="text" name="child" value="0" class="quantity-field" />
                                <input type="button" value="+" class="button-plus inc button" />
                              </div>
                            </div>
                          </div>
                        </div>
                      </li>
                      <li>3000.00</li>
                      <li>
                        <a data-bs-toggle="modal" data-bs-target="#delete" href="javascript:void(0);">
                          <img src="/assets/img/icons/delete-2.svg" alt="img" />
                        </a>
                      </li>
                    </ul>

                    <!-- Row 4 -->
                    <ul class="product-lists">
                      <li>
                        <div class="productimg">
                          <div class="productimgs">
                            <img src="/assets/img/product/product31.jpg" alt="img" />
                          </div>
                          <div class="productcontet">
                            <h4>
                              Strawberry
                              <a href="javascript:void(0);" class="ms-2" data-bs-toggle="modal" data-bs-target="#edit">
                                <img src="/assets/img/icons/edit-5.svg" alt="img" />
                              </a>
                            </h4>
                            <div class="productlinkset"><h5>PT001</h5></div>
                            <div class="increment-decrement">
                              <div class="input-groups">
                                <input type="button" value="-" class="button-minus dec button" />
                                <input type="text" name="child" value="0" class="quantity-field" />
                                <input type="button" value="+" class="button-plus inc button" />
                              </div>
                            </div>
                          </div>
                        </div>
                      </li>
                      <li>3000.00</li>
                      <li>
                        <a data-bs-toggle="modal" data-bs-target="#delete" href="javascript:void(0);">
                          <img src="/assets/img/icons/delete-2.svg" alt="img" />
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="split-card"></div>

                <div class="card-body pt-0 pb-2">
                  <div class="setvalue">
                    <ul>
                      <li><h5>Subtotal</h5><h6>55.00$</h6></li>
                      <li><h5>Tax</h5><h6>5.00$</h6></li>
                      <li class="total-value"><h5>Total</h5><h6>60.00$</h6></li>
                    </ul>
                  </div>

                  <div class="setvaluecash">
                    <ul>
                      <li><a href="javascript:void(0);" class="paymentmethod"><img src="/assets/img/icons/cash.svg" alt="" class="me-2" />Cash</a></li>
                      <li><a href="javascript:void(0);" class="paymentmethod"><img src="/assets/img/icons/debitcard.svg" alt="" class="me-2" />Debit</a></li>
                      <li><a href="javascript:void(0);" class="paymentmethod"><img src="/assets/img/icons/scan.svg" alt="" class="me-2" />Scan</a></li>
                    </ul>
                  </div>

                  <div class="btn-totallabel"><h5>Checkout</h5><h6>60.00$</h6></div>

                  <div class="btn-pos">
                    <ul>
                      <li><a class="btn"><img src="/assets/img/icons/pause1.svg" class="me-1" alt="" />Hold</a></li>
                      <li><a class="btn"><img src="/assets/img/icons/edit-6.svg" class="me-1" alt="" />Quotation</a></li>
                      <li><a class="btn"><img src="/assets/img/icons/trash12.svg" class="me-1" alt="" />Void</a></li>
                      <li><a class="btn"><img src="/assets/img/icons/wallet1.svg" class="me-1" alt="" />Payment</a></li>
                      <li>
                        <a class="btn" data-bs-toggle="modal" data-bs-target="#recents">
                          <img src="/assets/img/icons/transcation.svg" class="me-1" alt="" />Transaction
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div> <!-- /card -->
            </div> <!-- /RIGHT -->
          </div> <!-- /row -->
        </div>
      </div>
    </div>

    <!-- Modals (unchanged, just kept working) -->
    <div class="modal fade" id="calculator" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Define Quantity</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <div class="calculator-set">
              <div class="calculatortotal"><h4>0</h4></div>
              <ul>
                <li><a href="javascript:void(0);">1</a></li>
                <li><a href="javascript:void(0);">2</a></li>
                <li><a href="javascript:void(0);">3</a></li>
                <li><a href="javascript:void(0);">4</a></li>
                <li><a href="javascript:void(0);">5</a></li>
                <li><a href="javascript:void(0);">6</a></li>
                <li><a href="javascript:void(0);">7</a></li>
                <li><a href="javascript:void(0);">8</a></li>
                <li><a href="javascript:void(0);">9</a></li>
                <li><a href="javascript:void(0);" class="btn btn-closes"><img src="/assets/img/icons/close-circle.svg" alt="" /></a></li>
                <li><a href="javascript:void(0);">0</a></li>
                <li><a href="javascript:void(0);" class="btn btn-reverse"><img src="/assets/img/icons/reverse.svg" alt="" /></a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="holdsales" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Hold order</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <div class="hold-order"><h2>4500.00</h2></div>
            <div class="form-group"><label>Order Reference</label><input type="text" /></div>
            <div class="para-set">
              <p>
                The current order will be set on hold. You can retreive this order from the pending order button.
                Providing a reference to it might help you to identify the order more quickly.
              </p>
            </div>
            <div class="col-lg-12">
              <a class="btn btn-submit me-2">Submit</a>
              <a class="btn btn-cancel" data-bs-dismiss="modal">Cancel</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="edit" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Order</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Product Price</label><input type="text" value="20" /></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Product Price</label><select class="select"><option>Exclusive</option><option>Inclusive</option></select></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label> Tax</label><div class="input-group"><input type="text" /><a class="scanner-set input-group-text"> % </a></div></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Discount Type</label><select class="select"><option>Fixed</option><option>Percentage</option></select></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Discount</label><input type="text" value="20" /></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Sales Unit</label><select class="select"><option>Kilogram</option><option>Grams</option></select></div></div>
            </div>
            <div class="col-lg-12">
              <a class="btn btn-submit me-2">Submit</a>
              <a class="btn btn-cancel" data-bs-dismiss="modal">Cancel</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="create" tabindex="-1" aria-labelledby="create" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Create</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Customer Name</label><input type="text" /></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Email</label><input type="text" /></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Phone</label><input type="text" /></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Country</label><input type="text" /></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>City</label><input type="text" /></div></div>
              <div class="col-lg-6 col-sm-12 col-12"><div class="form-group"><label>Address</label><input type="text" /></div></div>
            </div>
            <div class="col-lg-12">
              <a class="btn btn-submit me-2">Submit</a>
              <a class="btn btn-cancel" data-bs-dismiss="modal">Cancel</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="delete" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Order Deletion</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <div class="delete-order"><img src="/assets/img/icons/close-circle1.svg" alt="" /></div>
            <div class="para-set text-center">
              <p>The current order will be deleted as no payment has been <br /> made so far.</p>
            </div>
            <div class="col-lg-12 text-center">
              <a class="btn btn-danger me-2">Yes</a>
              <a class="btn btn-cancel" data-bs-dismiss="modal">No</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="recents" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Recent Transactions</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <div class="tabs-sets">
              <ul class="nav nav-tabs" id="myTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="purchase-tab" data-bs-toggle="tab" data-bs-target="#purchase" type="button" aria-controls="purchase" aria-selected="true" role="tab">Purchase</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" aria-controls="payment" aria-selected="false" role="tab">Payment</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="return-tab" data-bs-toggle="tab" data-bs-target="#return" type="button" aria-controls="return" aria-selected="false" role="tab">Return</button>
                </li>
              </ul>

              <div class="tab-content">
                <!-- Purchase -->
                <div class="tab-pane fade show active" id="purchase" role="tabpanel" aria-labelledby="purchase-tab">
                  <div class="table-top">
                    <div class="search-set"><div class="search-input">
                      <a class="btn btn-searchset"><img src="/assets/img/icons/search-white.svg" alt="" /></a>
                    </div></div>
                    <div class="wordset">
                      <ul>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="pdf"><img src="/assets/img/icons/pdf.svg" alt="" /></a></li>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="excel"><img src="/assets/img/icons/excel.svg" alt="" /></a></li>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="print"><img src="/assets/img/icons/printer.svg" alt="" /></a></li>
                      </ul>
                    </div>
                  </div>
                  <div class="table-responsive">
                    <table class="table datanew">
                      <thead><tr><th>Date</th><th>Reference</th><th>Customer</th><th>Amount</th><th class="text-end">Action</th></tr></thead>
                      <tbody>
                        <tr><td>2022-03-07</td><td>INV/SL0101</td><td>Walk-in Customer</td><td>$ 1500.00</td>
                          <td><a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/eye.svg" alt="" /></a>
                              <a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/edit.svg" alt="" /></a>
                              <a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/delete.svg" alt="" /></a></td>
                        </tr>
                        <tr><td>2022-03-07</td><td>INV/SL0101</td><td>Walk-in Customer</td><td>$ 1500.00</td>
                          <td><a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/eye.svg" alt="" /></a>
                              <a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/edit.svg" alt="" /></a>
                              <a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/delete.svg" alt="" /></a></td>
                        </tr>
                        <!-- add more rows as needed -->
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Payment -->
                <div class="tab-pane fade" id="payment" role="tabpanel">
                  <div class="table-top">
                    <div class="search-set"><div class="search-input">
                      <a class="btn btn-searchset"><img src="/assets/img/icons/search-white.svg" alt="" /></a>
                    </div></div>
                    <div class="wordset">
                      <ul>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="pdf"><img src="/assets/img/icons/pdf.svg" alt="" /></a></li>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="excel"><img src="/assets/img/icons/excel.svg" alt="" /></a></li>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="print"><img src="/assets/img/icons/printer.svg" alt="" /></a></li>
                      </ul>
                    </div>
                  </div>
                  <div class="table-responsive">
                    <table class="table datanew">
                      <thead><tr><th>Date</th><th>Reference</th><th>Customer</th><th>Amount</th><th class="text-end">Action</th></tr></thead>
                      <tbody>
                        <tr><td>2022-03-07</td><td>0101</td><td>Walk-in Customer</td><td>$ 1500.00</td>
                          <td><a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/eye.svg" alt="" /></a>
                              <a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/edit.svg" alt="" /></a>
                              <a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/delete.svg" alt="" /></a></td>
                        </tr>
                        <!-- add more rows as needed -->
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Return -->
                <div class="tab-pane fade" id="return" role="tabpanel">
                  <div class="table-top">
                    <div class="search-set"><div class="search-input">
                      <a class="btn btn-searchset"><img src="/assets/img/icons/search-white.svg" alt="" /></a>
                    </div></div>
                    <div class="wordset">
                      <ul>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="pdf"><img src="/assets/img/icons/pdf.svg" alt="" /></a></li>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="excel"><img src="/assets/img/icons/excel.svg" alt="" /></a></li>
                        <li><a data-bs-toggle="tooltip" data-bs-placement="top" title="print"><img src="/assets/img/icons/printer.svg" alt="" /></a></li>
                      </ul>
                    </div>
                  </div>
                  <div class="table-responsive">
                    <table class="table datanew">
                      <thead><tr><th>Date</th><th>Reference</th><th>Customer</th><th>Amount</th><th class="text-end">Action</th></tr></thead>
                      <tbody>
                        <tr><td>2022-03-07</td><td>0101</td><td>Walk-in Customer</td><td>$ 1500.00</td>
                          <td><a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/eye.svg" alt="" /></a>
                              <a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/edit.svg" alt="" /></a>
                              <a class="me-3" href="javascript:void(0);"><img src="/assets/img/icons/delete.svg" alt="" /></a></td>
                        </tr>
                        <!-- add more rows as needed -->
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </Master>
</template>

<style scoped>
/* Category list — single row, scrollable, active highlight like the screenshot */
.tabs {
  display: flex;
  align-items: center;
  gap: 14px;
  overflow-x: auto;
  padding: 0;
  margin: 0 0 18px 0;
  list-style: none;
  scrollbar-width: none;
}
.tabs::-webkit-scrollbar { display: none; }

.tabs > li {
  flex: 0 0 auto;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 2px 10px rgba(17, 23, 31, .06);
  padding: 10px 14px;
  cursor: pointer;
  transition: .2s;
}
.tabs > li:hover { transform: translateY(-1px); }
.tabs > li.active {
  outline: 2px solid #6f61ff;           /* purple border like sample */
  background: #f1eeff;
}

.tabs .product-details { display: flex; align-items: center; gap: 10px; }
.tabs img { width: 28px; height: 28px; object-fit: contain; }

/* optional: tidy up product cards if needed */
.productsetimg img { width: 100%; height: auto; display: block; }

/* Bootstrap tab header style override (if used elsewhere) */
.nav-tabs .nav-link { border: 0; color: #6c757d; }
.nav-tabs .nav-link.active { color: #000; border-bottom: 2px solid var(--bs-primary); background-color: transparent; }
</style>
