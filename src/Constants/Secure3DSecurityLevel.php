<?php

namespace Omnipay\Garantibbva\Constants;

/**
 * ## 3D Secure Security Levels
 * ### **CUSTOM_PAY**
 * - **Common Card / Futures Sale**
 * - This is for standard card payments without 3D Secure authentication
 * - The transaction bypasses 3D Secure verification
 * - Used for regular card transactions or future sales
 *
 * ### **3D_PAY**
 * - **Standard 3D Pay**
 * - Basic 3D Secure authentication
 * - Customer is redirected to their bank's authentication page
 * - Requires password/SMS verification from the cardholder's bank
 * - Standard security level for most e-commerce transactions
 *
 * ### **3D_FULL**
 * - **3D Pay FULL**
 * - Full 3D Secure authentication with maximum security
 * - Complete liability shift to the bank
 * - Strongest authentication requirements
 * - Provides the highest level of fraud protection
 *
 * ### **3D_HALF**
 * - **3D Pay HALF**
 * - Partial 3D Secure authentication
 * - Less stringent authentication requirements compared to 3D_FULL
 * - Balance between security and user experience
 * - May have shared liability between merchant and bank
 *
 * ## Key Differences
 *
 * | Security Level | Authentication | Liability | Use Case |
 * | --- | --- | --- | --- |
 * | **CUSTOM_PAY** | None | Merchant | Regular transactions |
 * | **3D_PAY** | Standard | Shared/Bank | Most e-commerce |
 * | **3D_FULL** | Maximum | Bank | High-risk transactions |
 * | **3D_HALF** | Partial | Shared | Balanced approach |
 */
class Secure3DSecurityLevel
{
    public const CUSTOM_PAY = 'CUSTOM_PAY';
    public const PAY = '3D_PAY';
    public const FULL = '3D_FULL';
    public const HALF = '3D_HALF';
}
