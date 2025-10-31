beta测试中，查询模型推理服务调用量

## 调试

API Explorer

您可以通过 API Explorer 在线发起调用，无需关注签名生成过程，快速获取调用结果。

[去调试](https://api.volcengine.com/api-explorer/?action=GetUsage&groupName=%E6%9F%A5%E8%AF%A2%E7%94%A8%E9%87%8F&serviceCode=ark&version=2024-01-01)

## 请求参数

下表仅列出该接口特有的请求参数和部分公共参数。更多信息请见[公共参数](https://www.volcengine.com/docs/6369/67268)。

参数

类型

是否必填

示例值

描述

Action

String

是

GetUsage

要执行的操作，取值：GetUsage。

Version

String

是

2024-01-01

API的版本，取值：2024-01-01。

BatchJobId

String

否

\-

批量推理任务 ID

Scenes

Array of String

否

Ordinary

场景

ProjectName

String

否

default

-   资源所属的项目名称，默认值为default。
-   若资源不在默认项目中，需填写正确的项目名称，获取项目名称，请查看[文档](https://www.volcengine.com/docs/82379/1359411#%E5%A6%82%E4%BD%95%E8%8E%B7%E5%8F%96%E9%A1%B9%E7%9B%AE%E5%90%8D%E7%A7%B0%EF%BC%88project-name%EF%BC%89%EF%BC%9F)。

EndpointIds

Array of String

否

test-ep-id

接入点 id

StartTime

Integer

是

1731945600

开始时间

EndTime

Integer

是

1731945600

结束时间

Interval

Integer

是

3600

查询粒度，按天：86400，按小时：3600

## 返回参数

下表仅列出本接口特有的返回参数。更多信息请参见[返回结构](https://www.volcengine.com/docs/6369/80336)。

参数

类型

示例值

描述

UsageResults

Array of Object

\[  
{  
"Name": "PromptTokens",  
"MetricItems": \[  
{  
"Values": \[  
{  
"Timestamp": 1731945600,  
"Value": 1529525715  
}  
\]  
}  
\]  
},  
{  
"Name": "CompletionTokens",  
"MetricItems": \[  
{  
"Values": \[  
{  
"Timestamp": 1731945600,  
"Value": 40207324  
}  
\]  
}  
\]  
}  
\]

查询结果

## 请求示例

    {
      "StartTime": 1731945600,
      "EndTime": 1731945600,
      "Interval": 3600
    }


## 返回示例

    {
      "ResponseMetadata": {
        "RequestId": "20250122174923134189010230F8DFF4",
        "Action": "GetUsage",
        "Version": "2024-01-01",
        "Service": "ark",
        "Region": "cn-beijing"
      },
      "Result": {
        "UsageResults": [
          {
            "Name": "PromptTokens",
            "MetricItems": [
              {
                "Tags": [
                  {
                    "Key": "EndpointId",
                    "Value": "test-ep-id"
                  }
                ],
                "Values": [
                  {
                    "Timestamp": 1731949200,
                    "Value": 20826827
                  }
                ]
              }
            ]
          }
        ]
      }
    }


## 错误码

下表为您列举了该接口与业务逻辑相关的错误码。公共错误码请参见[公共错误码](https://www.volcengine.com/docs/82379/1299023)文档。

状态码

错误码

错误信息

说明

400

MissingParameter.{{Parameter}}

The required parameter {{Parameter}} is missing.

缺少必要的请求参数。请确认请求参数后重试。

400

InvalidParameter.{{Parameter}}

The specified parameter {{Parameter}} is invalid.

请求参数值不合法。请检查参数值的正确性后重试。

404

NotFound.{{Parameter}}

The specified {{ResourceType}} {{ResourceContent}} is not found.

指定资源找不到。请确认参数后重试。

500

InternalError

The request has failed due to an unknown error.

未知错误，请稍后重试。如果多次尝试仍失败，请提交工单。
