<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>


    <style>
@font-face {
                font-family: 'Prompt';
                src: url({{ storage_path("fonts/Prompt-Regular.ttf") }}) format("truetype");
                font-weight: 400;
                font-style: normal;
            }

        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6, {
            font-family: 'Prompt', sans-serif !important;
        }

        span,
        p,
        li,
        strong,
        option,
        label,
        input,
        a,
        b {
            font-family: 'Prompt', sans-serif !important;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 26px
        }
        h3 {
            font-size: 15px
        }
        .prompt-regular {
        font-family: "Prompt", serif !important;
        font-weight: 400 !important;
        font-style: normal !important;
        }

        p {
            margin: 0;
            padding: 0;
            font-size: 12px;
            font-weight: 400;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            height: 50px
        }

        .header img {
            max-width: 200px;
            margin-bottom: 10px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .details {
            width: 48%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 0.2px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 13px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
        }

        .footer p {
            font-size: 12px;
            color: #666;
        }

        .total {
            text-align: right;
            font-weight: bold;
        }

        .total-value {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .add_cus{
            margin-top: 0px; font-size: 13px; line-height: 10px
        }
        .mt-5{
            margin-top: 5px
        }
        .footer-detail {
            text-align: center;
            font-size: 13px
        }
    </style>

</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div style="width: 100%;">
                <div style="width: 50%; float:left">
                    <h1 style="float:left">ใบเสร็จรับเงิน</h1>
                </div>

                <div style="width: 50%;float:right">
                    <img src="{{ public_path('img/loadmaster_logo_v2.png') }}" alt="Company Logo" style="float:right">
                </div>
            </div>


        </div>

        <div style="width: 100%; text-align: right;">
            <div style="font-size: 13px; line-height: 10px">
                <p>
                <b style="font-size: 16px; color: #033169">บริษัท โลคมาสเตอร์ โลจิสติกส์ จำกัด 22</b><br>
                305 ซอยพระรามที่ 2 แขวงบางมด เขตจอมทอง กรุงเทพมหานคร 10150<br>
                โทรศัพท์: 099-276-2487<br>
                ลขประจำตัวผู้เสียภาษี: 0105567110129</p>
                <p style="font-weight: Regular;">ทดลอง 555+
                </p>
            </div>

        </div>

        <!-- Invoice Details -->
        <table style="width: 100%; margin-top: 20px;">
            <tr>
                <td style="width: 65%; vertical-align: top;">
                    <h3>บริษัท ทัพพีพิศ พิลส์ จำกัด 2 PAKNAM</h3>
                    <b class="add_cus mt-5 prompt-regular">ที่อยู่ : เลขที่ 75/19-20 ถนนศรีสมุทร ตำบลปากน้ำ อำเภยเมืองสมุทรปราการ จังหวัดสมุทรปราการ 10270</b>
                    <p class="add_cus mt-5">โทร : 0625418356</p>
                    <p class="add_cus mt-5">เลขประจำตัวผู้เสียภาษี : 0125566039617</p>
                </td>
                <td style="width: 35%; vertical-align: top; text-align: right;">
                    <p class="add_cus mt-5">เลขที่ : LML202410004</p>
                    <p class="add_cus mt-5">วันที่  : 09/10/2024</p>
                </td>
            </tr>
        </table>

        <!-- Product Table -->
        <table>
            <thead>
                <tr>
                    <th style="  text-align: center;  width: 50px !important;">#</th>
                    <th style=" text-align: center;  width: 120px !important;;">No. Code</th>
                    <th style="text-align: center;">รายการ DESCRIPTION</th>
                    <th style="text-align: center; ">จำนวนเงินสุทธิ (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style=" text-align: center">1</td>
                    <td style=" text-align: center">xxx</td>
                    <td style=" text-align: center">ค่าขนส่ง</td>
                    <td style="text-align: center">3,284.00</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="total">รวมมูลค่าสินค้า:</td>
                    <td >$320.00</td>
                </tr>
                <tr>
                    <td colspan="3" class="total">ภาษีมูลค่าเพิ่ม ณ อัตรา 1%:</td>
                    <td >$64.00</td>
                </tr>
                <tr>
                    <td colspan="3" class="total total-value">รวมจำนวนเงินทั้งสิ้น:</td>
                    <td  class="total-value">$384.00</td>
                </tr>
            </tfoot>
        </table>


                                <div style=" width: 100%; margin-top:15px;">
                                    <div style="width: 50%; float:left; text-align: center; font-size: 13px">
                                        <div>ได้รับใบเสร็จรับเงิน ตามรายการไว้ถูกต้องแล้ว</div>
                                        <div style="margin-top:5px; ">STATEMENT RECEIVED</div>
                                        <div
                                            style="justify-content: space-around; align-items: center; display: flex; margin-top: 20px; ">
                                            <div style=" width: 50%; float:left">
                                                ..................................................................
                                            </div>
                                            <div style="width: 50%; float:right; font-size: 12px">Date........../........../..........</div>
                                        </div>
                                    </div>
                                    <div style="width: 50%; float:right; text-align: center;">
                                        <div style="font-size: 13px">ลงนาม บริษัท โลคมาสเตอร์ โลจิสติกส์ จำกัด</div>
                                        <div
                                            style="justify-content: space-around; align-items: center; display: flex; margin-top: 20px">
                                            <div style="width: 50%; float:left; text-align: center; margin-top: 8px; font-size: 12px">
                                                <img src={{ public_path('img/1730228290286.jpg') }}
                                                    style="height: auto; width: 100px; margin-bottom: -15px" />
                                                <br>
                                                ................................................................<br>
                                                (ผู้รับเงิน)
                                            </div>
                                            <div style="width: 50%; float:right; font-size: 12px; margin-top: 15px">
                                                09/10/2024
                                                <br>
                                                (วันที่ออกเอกสาร)
                                            </div>
                                        </div>
                                    </div>

                                </div>


        {{-- <!-- Footer -->
        <div class="footer" style="width: 100%; display:block">
            <p style="font-size: 14px; color: #033169 ">บริษัท โลคมาสเตอร์ โลจิสติกส์ จำกัด</p>
            <p style="line-height: 10px; margin-top:5px">ติดตามสถานะพัสดุได้ทุกที่ ทุกเวลา ด้วยแอปพลิเคชันโลคมาสเตอร์</p>
            <p style="line-height: 10px">ขอบคุณที่ไว้วางใจในบริการของเรา!</p>
        </div> --}}
    </div>
</body>

</html>
