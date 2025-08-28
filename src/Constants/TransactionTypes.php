<?php

namespace Omnipay\Garantibbva\Constants;

class TransactionTypes
{
    public const SALES = 'sales'; // Satış/Taksitli Satış.
    public const VOID = 'void';
    public const REFUND = 'refund';
    public const PREAUTH = 'preauth'; // Tekrarlı satış işlem tipinde value değeri.
                                      // Bonus kullanımında işlem tipinde value değeri.
                                      // Ön otorizasyon işlem tipinde value değeri.
    public const POSTAUTH = 'postauth';
    public const PARTIALVOID = 'partialvoid';
    public const ORDERINQ = 'orderinq';
    public const ORDERHISTORYINQ = 'orderhistoryinq';
    public const ORDERLISTINQ = 'orderlistinq';
    public const REWARDINQ = 'rewardinq';
    public const DCCINQ = 'dccinq'; // DCC işlem tipinde value değeri.
    public const COMMERCIALCARD = 'commercialcard'; // Ortak Kart işlem tipinde value değeri.
    public const EXTENDEDCREDIT = 'extendedcredit'; // Futures Sale işlem tipinde value değeri.
}
