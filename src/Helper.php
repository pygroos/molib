<?php
/**
 * 	This file is part of the moext/molib package.
 *
 *	(c)	Moext <dev@moext.org>
 * 
 *	For the full copyright and license information, please view the LICENSE
 * 	file that was distributed with this source code. 
 */
namespace Moext\Molib;

class Helper
{
	
	//////////////////////////////////////////////////////////////////////
	///
	///		Parameters Validator
	///
	//////////////////////////////////////////////////////////////////////
	
	static function isExistString( $sStr, $bTrim = false )
	{
		$bRet	= false;

		if ( ! is_bool( $bTrim ) )
		{
			return false;
		}

		if ( is_string( $sStr ) || is_numeric( $sStr ) )
		{
			$sStr	= $bTrim ? strval( trim( $sStr ) ) : strval( $sStr );
			$bRet	= ( strlen( $bTrim ? trim( $sStr ) : $sStr ) > 0 );
		}

		return $bRet;
	}

	static function isMobile( $sStr, $bTrim = false )
	{
		if ( ! self::isExistString( $sStr ) )
		{
			return false;
		}

		if ( ! is_bool( $bTrim ) )
		{
			return false;
		}

		$sReExp	= '/^(?:13|14|15|17|18)[0-9]{9}$/';
		$sStr	= ( $bTrim ? trim( $sStr ) : $sStr );

		return ( 1 == preg_match( $sReExp, $sStr ) );
	}

	static function isArrayWithKeys( $arrData, $keys )
	{
		//
		//	arrData		- array
		//	keys 		- keys array, like: ['key1','key2'......]
		//				- key string, like: 'key1'
		//	return 		- true/false
		//
		if ( ! is_array( $arrData ) )
		{
			return false;
		}

		$bRet = false;

		if ( is_array( $keys ) && count( $keys ) > 0 && count( $arrData ) > 0 )
		{
			// keys is a list in array
			// check if arrData have the specified keys
			$bRet = ( count( $arrData ) == count( array_intersect( $keys, array_keys( $arrData ) ) ) );
		}
		else if ( self::isExistString( $keys ) )
		{
			// keys is a key in string
			$bRet = array_key_exists( $keys, $arrData );
		}
		else
		{
			// keys is null
			$bRet = ( count( $arrData ) > 0 );
		}

		return $bRet;
	}



	//////////////////////////////////////////////////////////////////////
	///
	///		Builder
	///
	//////////////////////////////////////////////////////////////////////
	
	static function randomString( $nLength = 6, $bNumeric = false )
	{
		$sRet = '';
		$sChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		if ( $bNumeric )
		{
			$sChars = '0123456789';
		}

		$nCharLen = strlen( $sChars );
		for ( $i = 0; $i < $nLength; $i ++ )
		{
			$sRet .= $sChars[ rand( 0, $nCharLen - 1 ) ];
		}

		return $sRet;
	}

	static function genUniqueId( $bOpt = false )
	{
		$sUid = '';

		if ( function_exists( 'com_create_guid' ) )
		{
			if ( $bOpt )
			{
				$sUid = com_create_guid();
			}
			else
			{
				$sUid = trim( com_create_guid(), '{}' );
			}
		}
		else
		{
			mt_srand( (double)microtime() * 10000 );    			//  optional for php 4.2.0 and up.
			$sCharId = strtolower( md5( uniqid( rand(), true ) ) );
			$sHyphen = chr( 45 );    				  				//  "-"
			$sLeftCurly  = $bOpt ? chr(123) : '';     				//  "{"
			$sRightCurly = $bOpt ? chr(125) : '';     				//  "}"
			$sUid = $sLeftCurly
				. substr( $sCharId, 0, 8 ) . $sHyphen
				. substr( $sCharId, 8, 4 ) . $sHyphen
				. substr( $sCharId, 12, 4 ) . $sHyphen
				. substr( $sCharId, 16, 4 ) . $sHyphen
				. substr( $sCharId, 20, 12 )
				. $sRightCurly;
		}
		
		return strval( $sUid );
	}



	//////////////////////////////////////////////////////////////////////
	///
	///		Calculate
	///
	//////////////////////////////////////////////////////////////////////
	
	static function arrDepth( $array )
	{
		if ( ! is_array( $array ) )
		{
			return 0;
		}

		$nMaxDepth = 1;
		foreach ( $array as $value )
		{
			if ( is_array( $value ) )
			{
				$nDepth = self::arrDepth( $value ) + 1;
				if ( $nDepth > $nMaxDepth )
				{
					$nMaxDepth = $nDepth;
				}
			}
		}

		return $nMaxDepth;
	}

	//
	//	When finds a value in a very large one-dimensional array
	//	Conversion the array to string, search in the string 
	//
	static function inArray( $sItem, $arrSearch )
	{
		$sStr  = implode( ',', $arrSearch );
		$sStr  = ',' . $sStr . ',';
		$sItem = ',' . $sItem . ',';

		return false !== strpos( $sItem, $sStr ) ? true : false;
	}

	static function getIp()
	{
		$sIp = '';

	    if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) && self::isExistString( $_SERVER['HTTP_X_REAL_IP'] ) ) 
	    {
	        $sIp = $_SERVER['HTTP_X_REAL_IP'];
	    } 
	    elseif ( isset( $_SERVER['REMOTE_ADDR'] ) && self::isExistString( $_SERVER['REMOTE_ADDR'] ) ) 
	    {
	        $sIp = $_SERVER['REMOTE_ADDR'];
	    } 
	    elseif ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && self::isExistString( $_SERVER['HTTP_CLIENT_IP'] ) ) 
	    {
	        $sIp = $_SERVER['HTTP_CLIENT_IP'];
	    } 
	    elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && self::isExistString( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) 
	    {
	        $sIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    } 

	    return $sIp;
	}

	
}
