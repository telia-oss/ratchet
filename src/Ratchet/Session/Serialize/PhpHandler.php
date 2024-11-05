<?php
namespace Ratchet\Session\Serialize;

class PhpHandler implements HandlerInterface {
    /**
     * Simply reverse behaviour of unserialize method.
     * {@inheritdoc}
     */
    function serialize(array $data) {
        $preSerialized = array();
        $serialized = '';

        if (count($data)) {
            foreach ($data as $bucket => $bucketData) {
                $preSerialized[] = $bucket . '|' . serialize($bucketData);
            }
            $serialized = implode('', $preSerialized);
        }

        return $serialized;
    }

    /**
     * {@inheritdoc}
     * @throws UnexpectedValueException If there is a problem parsing the data
     */
    public function unserialize($raw)
    {
        $returnData = array();
        $offset     = 0;
        $len        = strlen($raw);

        while ($offset < $len) {
            $pos = strpos($raw, "|", $offset);
            if ($pos === false) {
                throw new \UnexpectedValueException("Invalid data, '|' not found at offset $offset");
            }

            $varname = substr($raw, $offset, $pos - $offset);
            $offset  = $pos + 1;

            // Attempt to find the end of the serialized data
            $dataOffset = $offset;
            $braceCount = 0;
            $inString   = false;

            for (; $dataOffset < $len; $dataOffset++) {
                $char = $raw[$dataOffset];

                if ($char === '"' && $raw[$dataOffset - 1] !== '\\') {
                    $inString = !$inString;
                }

                if (!$inString) {
                    if ($char === '{') {
                        $braceCount++;
                    } elseif ($char === '}') {
                        $braceCount--;
                        if ($braceCount === 0) {
                            $dataOffset++;
                            break;
                        }
                    } elseif ($char === ';' && $braceCount === 0) {
                        $dataOffset++;
                        break;
                    }
                }
            }

            $serializedData = substr($raw, $offset, $dataOffset - $offset);
            $data = unserialize($serializedData);

            if ($data === false && $serializedData !== 'b:0;') {
                throw new \UnexpectedValueException("Unable to unserialize data for variable '$varname'");
            }

            $returnData[$varname] = $data;
            $offset = $dataOffset;
        }

        return $returnData;
    }
}
