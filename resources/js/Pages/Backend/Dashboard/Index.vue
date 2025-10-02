<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import VueApexCharts from "vue3-apexcharts";
import { ref } from "vue";
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatNumber, dateFmt } = useFormatters()

const series = [
    {
        name: "Sales",
        data: [50, 45, 60, 70, 50, 45, 60, 70],
    },
    {
        name: "Purchase",
        data: [-20, -80, -25, -70, -15, -20, -35, -30],
    },
];

const chartOptions = {
    chart: {
        type: "bar",
        height: 350,
        stacked: true,
        toolbar: { show: false },
    },
    colors: ["#65FA9E", "#EA5455"], // green, red
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: "20%",
            borderRadius: 3,
            dataLabels: {
                position: "top",
            },
        },
    },
    dataLabels: {
        enabled: true,
        offsetY: -8,
        style: {
            fontSize: "12px",
            colors: ["#333"],
        },
        formatter: (val) => Math.abs(val), // Hide minus sign
    },
    xaxis: {
        categories: [
            "Jan",
            "Feb",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
        ],
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        min: -60,
        max: 90,
        labels: {
            formatter: (val) => Math.abs(val),
        },
    },
    legend: {
        position: "top",
        horizontalAlign: "left",
        markers: {
            radius: 12,
        },
    },
    grid: {
        borderColor: "#e0e0e0",
        strokeDashArray: 4,
    },
};
const totalPurchaseDueMinor = 123123123.33      // 307,144.00
const totalSalesDueMinor    = 438500        // 4,385.00
const totalSaleAmountMinor  = 38565650      // 385,656.50
const anotherSaleMinor      = 40000         // 400.00

const customersCount  = 1.400
const suppliersCount  = 1030
const purchaseInvCnt  = 1020
const salesInvCnt     = 1435
</script>
<template>
    <Head title="Dashboard" />

    <Master>
        <div class="page-wrapper">
            <div class="row">
    <!-- Total Purchase Due -->
    <div class="col-lg-3 col-sm-6 col-12">
      <div class="dash-widget">
        <div class="dash-widgetimg">
          <span><img src="assets/img/icons/dash1.svg" alt="img" /></span>
        </div>
        <div class="dash-widgetcontent">
          <h5>{{ formatMoney(totalPurchaseDueMinor) }}</h5>
          <h6>Total Purchase Due</h6>
        </div>
      </div>
    </div>

    <!-- Total Sales Due -->
    <div class="col-lg-3 col-sm-6 col-12">
      <div class="dash-widget dash1">
        <div class="dash-widgetimg">
          <span><img src="assets/img/icons/dash2.svg" alt="img" /></span>
        </div>
        <div class="dash-widgetcontent">
          <h5>{{ formatMoney(totalSalesDueMinor) }}</h5>
          <h6>Total Sales Due</h6>
        </div>
      </div>
    </div>

    <!-- Total Sale Amount -->
    <div class="col-lg-3 col-sm-6 col-12">
      <div class="dash-widget dash2">
        <div class="dash-widgetimg">
          <span><img src="assets/img/icons/dash3.svg" alt="img" /></span>
        </div>
        <div class="dash-widgetcontent">
          <h5>{{ formatMoney(totalSaleAmountMinor) }}</h5>
          <h6>Total Sale Amount</h6>
        </div>
      </div>
    </div>

    <!-- Another Amount (rename to what it actually is) -->
    <div class="col-lg-3 col-sm-6 col-12">
      <div class="dash-widget dash3">
        <div class="dash-widgetimg">
          <span><img src="assets/img/icons/dash4.svg" alt="img" /></span>
        </div>
        <div class="dash-widgetcontent">
          <h5>{{ formatMoney(anotherSaleMinor) }}</h5>
          <h6>Total Sale Amount</h6>
        </div>
      </div>
    </div>

    <!-- Counts -->
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
      <div class="dash-count">
        <div class="dash-counts">
          <h4>{{ formatNumber(customersCount) }}</h4>
          <h5>Customers</h5>
        </div>
        <div class="dash-imgs"><i data-feather="user"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6 col-12 d-flex">
      <div class="dash-count das1">
        <div class="dash-counts">
          <h4>{{ formatNumber(suppliersCount) }}</h4>
          <h5>Suppliers</h5>
        </div>
        <div class="dash-imgs"><i data-feather="user-check"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6 col-12 d-flex">
      <div class="dash-count das2">
        <div class="dash-counts">
          <h4>{{ formatNumber(purchaseInvCnt) }}</h4>
          <h5>Purchase Invoice</h5>
        </div>
        <div class="dash-imgs"><i data-feather="file-text"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6 col-12 d-flex">
      <div class="dash-count das3">
        <div class="dash-counts">
          <h4>{{ formatNumber(salesInvCnt) }}</h4>
          <h5>Sales Invoice</h5>
        </div>
        <div class="dash-imgs"><i data-feather="file"></i></div>
      </div>
    </div>
  </div>

  <!-- Charts + Recently Added Products -->
  <div class="row">
    <div class="col-lg-7 col-sm-12 col-12 d-flex">
      <div class="card flex-fill">
        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Purchase & Sales</h5>
          <div class="graph-sets">
            <ul>
              <li><span>Sales</span></li>
              <li><span>Purchase</span></li>
            </ul>
            <div class="dropdown">
              <button class="btn btn-white btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                      data-bs-toggle="dropdown" aria-expanded="false">
                2022 <img src="assets/img/icons/dropdown.svg" alt="img" class="ms-2" />
              </button>
              <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <li><a href="javascript:void(0);" class="dropdown-item">2022</a></li>
                <li><a href="javascript:void(0);" class="dropdown-item">2021</a></li>
                <li><a href="javascript:void(0);" class="dropdown-item">2020</a></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="card-body">
          <VueApexCharts type="bar" height="350" :options="chartOptions" :series="series" />
        </div>
      </div>
    </div>

    <div class="col-lg-5 col-sm-12 col-12 d-flex">
      <div class="card flex-fill">
        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0">Recently Added Products</h4>
          <div class="dropdown">
            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="dropset">
              <i class="fa fa-ellipsis-v"></i>
            </a>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <li><a href="productlist.html" class="dropdown-item">Product List</a></li>
              <li><a href="addproduct.html" class="dropdown-item">Product Add</a></li>
            </ul>
          </div>
        </div>

        <div class="card-body">
          <div class="table-responsive dataview">
            <table class="table datatable">
              <thead>
                <tr>
                  <th>Sno</th>
                  <th>Products</th>
                  <th>Price</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(p, idx) in recentProducts" :key="p.id">
                  <td>{{ idx + 1 }}</td>
                  <td class="productimgname">
                    <a href="productlist.html" class="product-img">
                      <img :src="p.img" :alt="p.name" />
                    </a>
                    <a href="productlist.html">{{ p.name }}</a>
                  </td>
                  <td>{{ formatMoney(p.priceMinor) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
            <div class="card mb-0">
                <div class="card-body">
                    <h4 class="card-title">Expired Products</h4>
                    <div class="table-responsive dataview">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>SNo</th>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Brand Name</th>
                                    <th>Category Name</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <a href="javascript:void(0);">IT0001</a>
                                    </td>
                                    <td class="productimgname">
                                        <a
                                            class="product-img"
                                            href="productlist.html"
                                        >
                                            <img
                                                src="assets/img/product/product2.jpg"
                                                alt="product"
                                            />
                                        </a>
                                        <a href="productlist.html">Orange</a>
                                    </td>
                                    <td>N/D</td>
                                    <td>Fruits</td>
                                    <td>{{ dateFmt('03-12-2022') }}</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>
                                        <a href="javascript:void(0);">IT0002</a>
                                    </td>
                                    <td class="productimgname">
                                        <a
                                            class="product-img"
                                            href="productlist.html"
                                        >
                                            <img
                                                src="assets/img/product/product3.jpg"
                                                alt="product"
                                            />
                                        </a>
                                        <a href="productlist.html">Pineapple</a>
                                    </td>
                                    <td>N/D</td>
                                    <td>Fruits</td>
                                    <td>25-11-2022</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>
                                        <a href="javascript:void(0);">IT0003</a>
                                    </td>
                                    <td class="productimgname">
                                        <a
                                            class="product-img"
                                            href="productlist.html"
                                        >
                                            <img
                                                src="assets/img/product/product4.jpg"
                                                alt="product"
                                            />
                                        </a>
                                        <a href="productlist.html">Stawberry</a>
                                    </td>
                                    <td>N/D</td>
                                    <td>Fruits</td>
                                    <td>19-11-2022</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>
                                        <a href="javascript:void(0);">IT0004</a>
                                    </td>
                                    <td class="productimgname">
                                        <a
                                            class="product-img"
                                            href="productlist.html"
                                        >
                                            <img
                                                src="assets/img/product/product5.jpg"
                                                alt="product"
                                            />
                                        </a>
                                        <a href="productlist.html">Avocat</a>
                                    </td>
                                    <td>N/D</td>
                                    <td>Fruits</td>
                                    <td>20-11-2022</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </Master>
</template>
