<?php

namespace App\Enums;

/**
 * All non-hub payment gateway types (hub types excluded per plan).
 */
enum PaymentMethodType: string
{
    case STRIPE = 'stripe';
    case PAYOUTS = 'payouts';
    case CARD = 'card';
    case FINIX = 'finix';
    case PLAID = 'plaid';
    case LOCKNPAY = 'locknpay';
    case CHECKBOOK = 'checkbook';
    case ONLINECHECKWRITER = 'onlinecheckwriter';
    case MASSPAY = 'masspay';
    case TABAPAY = 'tabapay';
    case PAYPAL = 'paypal';
    case EMS = 'ems';
    case LUMINO = 'lumino';
    case IPAYOUTS = 'i-payouts';
    case BEYOND = 'beyond';
    case SILAMONEY = 'silamoney';
    case NOWPAYMENT = 'NOWPAYMENT';
    case NEWSTRIPE = 'newstripe';
    case NEWPAYPAL = 'newpaypal';
    case DOTSPAYMENT = 'dots-payment';
    case RUNAPAYMENT = 'runa-payment';
    case CYBERSOURCE = 'cybersource-payment';
    case CASHAPP = 'cash-app';
    case TAILOREDAY = 'tailoreday';
    case TRANSAK = 'transak';
    case BLOKKO = 'blokko';
    case CLIQ = 'cliq';
    case ONEPAY = 'OnePay';
    case BANXA = 'banxa';
    case EMSNEW = 'ems-new';
    case WORLDPAY = 'worldpay';
    case GUARDARIAN = 'guardarian';
    case VENMOMANUL = 'venmo-manual';
    case MANUALCASHAPP = 'manual-cash-app';
    case OTHER = 'other';
}
